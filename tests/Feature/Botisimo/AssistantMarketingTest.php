<?php

namespace Tests\Feature\Botisimo;

use App\Jobs\Botisimo\SendCampaign;
use App\Models\Shop;
use App\Models\ShopBot;
use App\Models\ShopBotCampaign;
use App\Models\ShopBotConversation;
use App\Models\ShopBotCreditUsage;
use App\Models\ShopBotMessage;
use App\Models\ShopBotTemplate;
use App\Models\User;
use App\Services\Botisimo\CampaignSender;
use App\Services\Botisimo\Exceptions\BotisimoRateLimitException;
use App\Services\Botisimo\TemplateCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AssistantMarketingTest extends TestCase
{
    use RefreshDatabase;

    private User $vendor;

    private Shop $shop;

    private ShopBot $bot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        config([
            'botisimo.api_key' => 'bts_x',
            'botisimo.base_url' => 'https://botisimo.test',
            'botisimo.rate_limit.max_requests' => 10000,
            'botisimo.retry.max_attempts' => 1,
            'botisimo.credits.monthly_quota' => 5000,
        ]);

        [$this->vendor, $this->shop, $this->bot] = $this->vendorSetup('bot-1');
    }

    protected function tearDown(): void
    {
        Cache::flush();

        parent::tearDown();
    }

    private function vendorSetup(string $botId): array
    {
        $vendor = User::factory()->create(['role' => 'supplier']);
        $shop = Shop::factory()->create(['user_id' => $vendor->id]);
        $shop->subscriptions()->create(['plan' => 'standard', 'status' => 'active', 'ends_at' => now()->addDays(5)]);
        $bot = ShopBot::create([
            'shop_id' => $shop->id, 'botisimo_bot_id' => $botId, 'webhook_secret' => 's',
            'phone_number_id' => 'pn', 'status' => ShopBot::STATUS_ACTIVE,
        ]);

        return [$vendor, $shop, $bot];
    }

    private function route(string $name, array $params = []): string
    {
        return route("backoffice.supplier.assistant.{$name}", [$this->shop, ...$params]);
    }

    private function template(array $attributes = []): ShopBotTemplate
    {
        return $this->bot->templates()->create($attributes + [
            'name' => 'promo',
            'category' => 'MARKETING',
            'language' => 'fr',
            'status' => ShopBotTemplate::STATUS_APPROVED,
            'components' => [['type' => 'BODY', 'text' => 'Bonjour {{1}}']],
        ]);
    }

    private function campaign(ShopBotTemplate $template, array $overrides = []): ShopBotCampaign
    {
        return $this->bot->campaigns()->create($overrides + [
            'template_name' => $template->name,
            'status' => ShopBotCampaign::STATUS_SCHEDULED,
            'payload' => ['template_id' => $template->id, 'language' => 'fr', 'tags' => [], 'parameters' => ['Awa']],
        ]);
    }

    private function clients(int $count, int $from = 1): array
    {
        return collect(range($from, $from + $count - 1))
            ->map(fn ($i) => ['id' => "u{$i}", 'phone' => '2376'.str_pad((string) $i, 8, '0', STR_PAD_LEFT), 'opted_out' => false])
            ->all();
    }

    private function sendJob(ShopBotCampaign $campaign): void
    {
        (new SendCampaign($campaign->id))->handle(app(CampaignSender::class));
    }

    private function broadcasts(): array
    {
        return collect(Http::recorded())
            ->filter(fn ($pair) => str_ends_with($pair[0]->url(), '/v1/marketing/broadcast'))
            ->map(fn ($pair) => $pair[0])
            ->values()
            ->all();
    }

    // --- Clients ---

    public function test_client_list_is_scoped_to_the_bot_and_forwards_filters(): void
    {
        Http::fake(['botisimo.test/v1/clients*' => Http::response(['clients' => [
            ['id' => 'u1', 'phone' => '237699000000', 'name' => 'Awa', 'tags' => ['vip'], 'opted_out' => true],
        ]])]);

        $this->actingAs($this->vendor)
            ->get($this->route('clients.index').'?search=awa&tag=vip&opted_out=1')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Backoffice/Supplier/Assistant/Clients', false)
                ->where('clients.0.name', 'Awa')
                ->where('clients.0.opted_out', true)
                ->where('hasMore', false));

        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'bot_id=bot-1')
            && str_contains($r->url(), 'search=awa') && str_contains($r->url(), 'tag=vip')
            && str_contains($r->url(), 'opted_out=true') && str_contains($r->url(), 'limit=50'));
    }

    public function test_client_page_shows_a_message_when_botisimo_is_down(): void
    {
        Http::fake(['*' => Http::response([], 500)]);

        $this->actingAs($this->vendor)
            ->get($this->route('clients.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->whereType('loadError', 'string')->has('clients', 0));
    }

    public function test_adding_a_client_normalizes_the_number(): void
    {
        Http::fake(['botisimo.test/v1/clients' => Http::response(['id' => 'u1'], 201)]);

        $this->actingAs($this->vendor)
            ->post($this->route('clients.store'), ['phone' => '+237 699 00 00 00', 'name' => 'Awa', 'tags' => ['vip']])
            ->assertSessionHas('success');

        Http::assertSent(fn (Request $r) => $r['bot_id'] === 'bot-1' && $r['phone'] === '237699000000' && $r['tags'] === ['vip']);
    }

    public function test_duplicate_number_gives_a_field_error(): void
    {
        Http::fake(['*' => Http::response(['error' => 'exists'], 409)]);

        $this->actingAs($this->vendor)
            ->post($this->route('clients.store'), ['phone' => '237699000000'])
            ->assertSessionHasErrors('phone');
    }

    public function test_client_update_checks_ownership_first(): void
    {
        Http::fake(['botisimo.test/v1/clients/u1' => Http::response(['id' => 'u1', 'bot_id' => 'bot-1', 'phone' => '2376'])]);

        $this->actingAs($this->vendor)
            ->put($this->route('clients.update', ['u1']), ['name' => 'Awa', 'tags' => ['vip'], 'opted_out' => true])
            ->assertSessionHas('success');

        Http::assertSent(fn (Request $r) => $r->method() === 'PUT' && $r['opted_out'] === true && $r['tags'] === ['vip']);
    }

    public function test_a_vendor_cannot_change_a_client_of_another_bot_by_guessing_its_id(): void
    {
        Http::fake(['botisimo.test/v1/clients/*' => Http::response(['id' => 'x', 'bot_id' => 'bot-autre', 'phone' => '2376'])]);
        $this->actingAs($this->vendor);

        $this->put($this->route('clients.update', ['x']), ['tags' => [], 'opted_out' => false])->assertNotFound();
        $this->delete($this->route('clients.destroy', ['x']))->assertNotFound();

        Http::assertNotSent(fn (Request $r) => in_array($r->method(), ['PUT', 'DELETE'], true));
    }

    public function test_ownership_fails_closed_when_the_response_has_no_bot_id(): void
    {
        Http::fake(['botisimo.test/v1/clients/*' => Http::response(['id' => 'x', 'phone' => '2376'])]);

        $this->actingAs($this->vendor)->delete($this->route('clients.destroy', ['x']))->assertNotFound();

        Http::assertNotSent(fn (Request $r) => $r->method() === 'DELETE');
    }

    // --- Templates ---

    public function test_template_builder_produces_meta_components(): void
    {
        $this->assertSame('promo_de_noel', TemplateCatalog::slug('Promo de Noël !'));

        $this->assertSame([
            ['type' => 'BODY', 'text' => 'Bonjour {{1}}', 'example' => ['body_text' => [['Awa']]]],
            ['type' => 'FOOTER', 'text' => 'Maketu'],
            ['type' => 'BUTTONS', 'buttons' => [['type' => 'QUICK_REPLY', 'text' => 'Oui']]],
        ], TemplateCatalog::components([
            'body' => 'Bonjour {{1}}', 'examples' => ['Awa'], 'footer' => 'Maketu', 'buttons' => ['Oui', null, ''],
        ]));
    }

    public function test_creating_a_template_submits_it_and_tracks_it_as_pending(): void
    {
        Http::fake(['botisimo.test/v1/templates' => Http::response(['id' => 't1', 'status' => 'PENDING'], 201)]);

        $this->actingAs($this->vendor)->post($this->route('templates.store'), [
            'name' => 'Promo de Noël',
            'category' => 'MARKETING',
            'language' => 'fr',
            'body' => 'Bonjour {{1}}, -10 % sur {{2}}',
            'examples' => ['Awa', 'les sacs'],
        ])->assertSessionHas('success');

        $template = ShopBotTemplate::firstOrFail();
        $this->assertSame('promo_de_noel', $template->name);
        $this->assertSame('t1', $template->botisimo_template_id);
        $this->assertSame(ShopBotTemplate::STATUS_PENDING, $template->status);
        $this->assertSame(2, $template->variableCount());
        Http::assertSent(fn (Request $r) => $r['bot_id'] === 'bot-1' && $r['name'] === 'promo_de_noel');
    }

    public function test_template_validation(): void
    {
        Http::fake();
        $this->template(['name' => 'deja_pris']);
        $base = ['category' => 'MARKETING', 'language' => 'fr', 'name' => 'nouveau'];
        $this->actingAs($this->vendor);

        $this->post($this->route('templates.store'), $base + ['body' => 'A {{1}} {{3}}', 'examples' => ['a', 'b']])->assertSessionHasErrors('body');
        $this->post($this->route('templates.store'), $base + ['body' => 'A {{1}}'])->assertSessionHasErrors('examples');
        $this->post($this->route('templates.store'), ['name' => 'Déjà pris', 'body' => 'x'] + $base)->assertSessionHasErrors('name');
        $this->post($this->route('templates.store'), ['category' => 'AUTHENTICATION', 'body' => 'x'] + $base)->assertSessionHasErrors('category');

        Http::assertNothingSent();
    }

    public function test_opening_the_page_picks_up_metas_decision_at_most_once_a_minute(): void
    {
        $template = $this->template(['status' => ShopBotTemplate::STATUS_PENDING]);
        Http::fake(['botisimo.test/v1/templates*' => Http::response(['templates' => [
            ['id' => 't9', 'name' => 'promo', 'language' => 'fr', 'status' => 'approved'],
        ]])]);

        $this->actingAs($this->vendor)->get($this->route('templates.index'))->assertOk();
        $this->actingAs($this->vendor)->get($this->route('templates.index'))->assertOk();

        $this->assertSame(ShopBotTemplate::STATUS_APPROVED, $template->fresh()->status);
        $this->assertSame('t9', $template->fresh()->botisimo_template_id);
        Http::assertSentCount(1);
    }

    public function test_deleting_a_template_is_isolated(): void
    {
        Http::fake(['botisimo.test/v1/templates/t1' => Http::response([])]);
        $template = $this->template(['botisimo_template_id' => 't1']);
        [$intruder, $intruderShop] = $this->vendorSetup('bot-2');

        $this->actingAs($intruder)
            ->delete(route('backoffice.supplier.assistant.templates.destroy', [$intruderShop, $template]))
            ->assertNotFound();
        $this->assertModelExists($template);

        $this->actingAs($this->vendor)->delete($this->route('templates.destroy', [$template]))->assertSessionHas('success');
        $this->assertModelMissing($template);
    }

    // --- Campagnes ---

    public function test_estimate_excludes_opted_out_clients_and_duplicates(): void
    {
        Http::fake(['botisimo.test/v1/clients*' => Http::response(['data' => [
            ['id' => 'a', 'phone' => '237699000001', 'opted_out' => false],
            ['id' => 'b', 'phone' => '+237699000001', 'opted_out' => false],
            ['id' => 'c', 'phone' => '237699000002', 'opted_out' => true],
            ['id' => 'd', 'opted_out' => false],
        ]])]);

        $this->actingAs($this->vendor)
            ->getJson($this->route('campaigns.estimate').'?tags[]=vip')
            ->assertOk()
            ->assertJson(['recipients' => 1, 'credits' => 1, 'remaining' => 5000, 'exceeds_quota' => false]);

        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'opted_out=false') && str_contains($r->url(), 'tag=vip'));
    }

    public function test_campaign_needs_an_approved_template_of_this_shop(): void
    {
        Bus::fake();
        $pending = $this->template(['status' => ShopBotTemplate::STATUS_PENDING]);
        [, , $otherBot] = $this->vendorSetup('bot-2');
        $foreign = $otherBot->templates()->create([
            'name' => 'x', 'category' => 'MARKETING', 'language' => 'fr', 'status' => 'APPROVED',
            'components' => [['type' => 'BODY', 'text' => 'x']],
        ]);
        $this->actingAs($this->vendor);

        $this->post($this->route('campaigns.store'), ['template_id' => $pending->id])->assertSessionHasErrors('template_id');
        $this->post($this->route('campaigns.store'), ['template_id' => $foreign->id])->assertSessionHasErrors('template_id');
        $this->post($this->route('campaigns.store'), ['template_id' => $this->template(['name' => 'ok'])->id])->assertSessionHasErrors('parameters');

        $this->assertDatabaseCount('shop_bot_campaigns', 0);
        Bus::assertNotDispatched(SendCampaign::class);
    }

    public function test_launching_sends_to_reachable_clients_and_charges_the_shop(): void
    {
        Http::fake([
            'botisimo.test/v1/clients*' => Http::response(['data' => [
                ['id' => 'a', 'phone' => '237699000001', 'opted_out' => false],
                ['id' => 'b', 'phone' => '237699000002', 'opted_out' => true],
            ]]),
            'botisimo.test/v1/credits/balance' => Http::response(['balance' => 5000]),
            'botisimo.test/v1/marketing/broadcast' => Http::response(['campaign_id' => 'k1']),
        ]);
        $template = $this->template();

        $this->actingAs($this->vendor)->post($this->route('campaigns.store'), [
            'template_id' => $template->id, 'parameters' => ['Awa'], 'tags' => ['vip'],
        ])->assertSessionHas('success');

        $campaign = ShopBotCampaign::firstOrFail();
        $this->assertSame(ShopBotCampaign::STATUS_SENT, $campaign->status);
        $this->assertSame(1, $campaign->sent);
        $this->assertSame(1, $campaign->credits_used);
        $this->assertSame('k1', $campaign->botisimo_campaign_id);
        $this->assertArrayNotHasKey('recipients', $campaign->payload);
        $this->assertSame(1, (int) ShopBotCreditUsage::sum('credits'));

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/broadcast')
            && $r['bot_id'] === 'bot-1' && $r['template_name'] === 'promo' && $r['language'] === 'fr'
            && $r['recipients'] === ['237699000001'] && $r['parameters'] === ['Awa']);
    }

    public function test_large_audiences_are_split_in_batches_of_one_thousand(): void
    {
        Http::fake([
            'botisimo.test/v1/clients*' => Http::sequence()
                ->push(['data' => $this->clients(200)])->push(['data' => $this->clients(200, 201)])
                ->push(['data' => $this->clients(200, 401)])->push(['data' => $this->clients(200, 601)])
                ->push(['data' => $this->clients(200, 801)])->push(['data' => $this->clients(50, 1001)]),
            'botisimo.test/v1/credits/balance' => Http::response(['balance' => 5000]),
            'botisimo.test/v1/marketing/broadcast' => Http::response([]),
        ]);
        $campaign = $this->campaign($this->template());

        $this->sendJob($campaign);

        $batches = $this->broadcasts();
        $this->assertCount(2, $batches);
        $this->assertCount(1000, $batches[0]['recipients']);
        $this->assertCount(50, $batches[1]['recipients']);
        $this->assertSame(1050, $campaign->fresh()->sent);
        $this->assertEmpty(array_intersect($batches[0]['recipients'], $batches[1]['recipients']));
    }

    public function test_resuming_after_a_rate_limit_never_resends_a_batch(): void
    {
        Http::fake([
            'botisimo.test/v1/credits/balance' => Http::response(['balance' => 5000]),
            'botisimo.test/v1/marketing/broadcast' => Http::sequence()
                ->push([])
                ->push([], 429, ['Retry-After' => '30'])
                ->push([]),
        ]);
        $phones = array_column($this->clients(1500), 'phone');
        $campaign = $this->campaign($this->template(), [
            'status' => ShopBotCampaign::STATUS_SENDING,
            'recipients_count' => 1500,
        ]);
        $campaign->update(['payload' => ['recipients' => $phones, 'cursor' => 0] + $campaign->payload]);
        $sender = app(CampaignSender::class);

        try {
            $sender->send($campaign->fresh());
            $this->fail('Le rate limit doit remonter pour relâcher le job');
        } catch (BotisimoRateLimitException) {
            $this->assertSame(1000, $campaign->fresh()->payload['cursor']);
            $this->assertSame(ShopBotCampaign::STATUS_SENDING, $campaign->fresh()->status);
        }

        $sender->send($campaign->fresh());

        $batches = $this->broadcasts();
        $this->assertCount(3, $batches); // 1 000, le lot refusé, puis ce même lot rejoué
        $this->assertSame($batches[1]['recipients'], $batches[2]['recipients']);
        $this->assertSame(1500, $campaign->fresh()->sent);
        $this->assertSame(1500, (int) ShopBotCreditUsage::sum('credits'));
    }

    public function test_tier_limit_stops_the_campaign_without_retrying(): void
    {
        Http::fake([
            'botisimo.test/v1/clients*' => Http::response(['data' => $this->clients(3)]),
            'botisimo.test/v1/credits/balance' => Http::response(['balance' => 5000]),
            'botisimo.test/v1/marketing/broadcast' => Http::response(['error' => 'quota'], 422),
        ]);
        $campaign = $this->campaign($this->template());

        $this->sendJob($campaign);

        $campaign->refresh();
        $this->assertSame(ShopBotCampaign::STATUS_FAILED, $campaign->status);
        $this->assertStringContainsString('Plafond', $campaign->error);
        $this->assertSame(3, $campaign->failed);
        $this->assertArrayNotHasKey('recipients', $campaign->payload);
        $this->assertDatabaseCount('shop_bot_credit_usages', 0);
        $this->assertCount(1, $this->broadcasts());
    }

    public function test_server_error_is_not_retried_to_avoid_double_sending(): void
    {
        Http::fake([
            'botisimo.test/v1/clients*' => Http::response(['data' => $this->clients(2)]),
            'botisimo.test/v1/credits/balance' => Http::response(['balance' => 5000]),
            'botisimo.test/v1/marketing/broadcast' => Http::response([], 502),
        ]);
        config(['botisimo.retry.max_attempts' => 3]);
        $campaign = $this->campaign($this->template());

        $this->sendJob($campaign);

        $this->assertSame(ShopBotCampaign::STATUS_FAILED, $campaign->fresh()->status);
        $this->assertCount(1, $this->broadcasts());
    }

    public function test_campaign_is_all_or_nothing_against_the_quota(): void
    {
        Http::fake(['botisimo.test/v1/clients*' => Http::response(['data' => $this->clients(10)])]);
        $this->bot->update(['monthly_credit_quota' => 5]);
        $campaign = $this->campaign($this->template());

        $this->sendJob($campaign);

        $this->assertSame(ShopBotCampaign::STATUS_FAILED, $campaign->fresh()->status);
        $this->assertStringContainsString('quota', $campaign->fresh()->error);
        $this->assertSame([], $this->broadcasts());
    }

    public static function blockers(): array
    {
        return [
            'modèle révoqué' => [fn ($test, $template) => $template->update(['status' => 'REJECTED']), 'modèle'],
            'bot désactivé' => [fn ($test, $template) => $template->bot->update(['status' => ShopBot::STATUS_INACTIVE]), 'assistant'],
            'abonnement expiré' => [fn ($test, $template) => $template->bot->shop->subscriptions()->update(['ends_at' => now()->subDay()]), 'assistant'],
        ];
    }

    /** Une campagne planifiée peut partir bien après sa création. */
    #[DataProvider('blockers')]
    public function test_conditions_are_rechecked_at_send_time(\Closure $block, string $expected): void
    {
        Http::fake();
        $template = $this->template();
        $campaign = $this->campaign($template);
        $block($this, $template);

        $this->sendJob($campaign);

        $this->assertSame(ShopBotCampaign::STATUS_FAILED, $campaign->fresh()->status);
        $this->assertStringContainsString($expected, $campaign->fresh()->error);
        Http::assertNothingSent();
    }

    public function test_a_campaign_is_never_sent_twice(): void
    {
        Http::fake([
            'botisimo.test/v1/clients*' => Http::response(['data' => $this->clients(2)]),
            'botisimo.test/v1/credits/balance' => Http::response(['balance' => 5000]),
            'botisimo.test/v1/marketing/broadcast' => Http::response([]),
        ]);
        $campaign = $this->campaign($this->template());

        $this->sendJob($campaign);
        $this->sendJob($campaign);

        $this->assertCount(1, $this->broadcasts());
    }

    public function test_scheduler_launches_only_due_campaigns(): void
    {
        Bus::fake();
        $template = $this->template();
        $due = $this->campaign($template, ['scheduled_at' => now()->subMinute()]);
        $this->campaign($template, ['scheduled_at' => now()->addHour()]);

        $this->artisan('botisimo:dispatch-campaigns')->assertSuccessful();

        Bus::assertDispatchedTimes(SendCampaign::class, 1);
        Bus::assertDispatched(SendCampaign::class, fn ($job) => $job->campaignId === $due->id);
    }

    public function test_scheduling_does_not_send_now_and_can_be_cancelled(): void
    {
        Bus::fake();
        $template = $this->template();
        $this->actingAs($this->vendor);

        $this->post($this->route('campaigns.store'), [
            'template_id' => $template->id, 'parameters' => ['Awa'], 'scheduled_at' => now()->addDay()->toIso8601String(),
        ])->assertSessionHas('success');

        Bus::assertNotDispatched(SendCampaign::class);
        $campaign = ShopBotCampaign::firstOrFail();

        $this->post($this->route('campaigns.cancel', [$campaign]))->assertSessionHas('success');
        $this->assertSame(ShopBotCampaign::STATUS_CANCELLED, $campaign->fresh()->status);

        $this->sendJob($campaign);
        $this->assertSame(ShopBotCampaign::STATUS_CANCELLED, $campaign->fresh()->status);
    }

    public function test_past_date_is_refused(): void
    {
        $this->actingAs($this->vendor)->post($this->route('campaigns.store'), [
            'template_id' => $this->template()->id, 'parameters' => ['Awa'], 'scheduled_at' => now()->subHour()->toIso8601String(),
        ])->assertSessionHasErrors('scheduled_at');
    }

    public function test_campaign_routes_are_isolated(): void
    {
        Http::fake();
        $template = $this->template();
        $campaign = $this->campaign($template);
        [$intruder, $intruderShop] = $this->vendorSetup('bot-2');
        $this->actingAs($intruder);

        $this->get($this->route('campaigns.index'))->assertForbidden();
        $this->getJson($this->route('campaigns.estimate'))->assertForbidden();
        $this->post($this->route('campaigns.store'), ['template_id' => $template->id])->assertForbidden();
        $this->post($this->route('campaigns.cancel', [$campaign]))->assertForbidden();
        $this->post(route('backoffice.supplier.assistant.campaigns.cancel', [$intruderShop, $campaign]))->assertNotFound();

        $this->assertSame(ShopBotCampaign::STATUS_SCHEDULED, $campaign->fresh()->status);
    }

    public function test_history_syncs_remote_counters(): void
    {
        Http::fake(['botisimo.test/v1/*' => Http::response(['campaigns' => [['id' => 'k1', 'sent' => 7, 'failed' => 2]]])]);
        $campaign = $this->campaign($this->template(), ['botisimo_campaign_id' => 'k1', 'status' => ShopBotCampaign::STATUS_SENT]);

        $this->actingAs($this->vendor)->get($this->route('campaigns.index'))->assertOk();

        $this->assertSame(7, $campaign->fresh()->sent);
        $this->assertSame(2, $campaign->fresh()->failed);
    }

    // --- Modèle hors fenêtre de 24 h ---

    private function closedConversation(): ShopBotConversation
    {
        return $this->bot->conversations()->create([
            'botisimo_conversation_id' => 'c1', 'client_phone' => '237699000000',
            'last_inbound_at' => now()->subDays(3),
        ]);
    }

    public function test_template_reaches_a_client_outside_the_window_for_one_credit(): void
    {
        Http::fake([
            'botisimo.test/v1/clients*' => Http::response(['data' => []]),
            'botisimo.test/v1/credits/balance' => Http::response(['balance' => 5000]),
            'botisimo.test/v1/marketing/send' => Http::response(['message_id' => 'tpl-1']),
        ]);
        $conversation = $this->closedConversation();

        $this->actingAs($this->vendor)
            ->post($this->route('inbox.template', [$conversation]), ['template_id' => $this->template()->id, 'parameters' => ['Awa']])
            ->assertSessionMissing('error');

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/v1/marketing/send')
            && $r['to'] === '237699000000' && $r['template_name'] === 'promo' && $r['parameters'] === ['Awa']);

        $message = ShopBotMessage::firstOrFail();
        $this->assertSame('template', $message->type);
        $this->assertSame('tpl-1', $message->message_id);
        $this->assertSame(1, (int) ShopBotCreditUsage::sum('credits'));
    }

    public function test_page_offers_approved_templates_only_outside_the_window(): void
    {
        Http::fake(['*' => Http::response(['data' => []])]);
        $this->template();
        $this->template(['name' => 'en_attente', 'status' => 'PENDING']);
        $conversation = $this->closedConversation();

        $this->actingAs($this->vendor)
            ->get($this->route('inbox.show', [$conversation]))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('templates', 1)->where('templates.0.name', 'promo'));

        $conversation->update(['last_inbound_at' => now()]);

        $this->actingAs($this->vendor)
            ->get($this->route('inbox.show', [$conversation]))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('templates', 0));
    }

    public function test_opted_out_client_cannot_receive_a_template(): void
    {
        Http::fake(['botisimo.test/v1/clients*' => Http::response(['data' => [
            ['id' => 'u1', 'phone' => '237699000000', 'opted_out' => true],
        ]])]);

        $this->actingAs($this->vendor)
            ->post($this->route('inbox.template', [$this->closedConversation()]), ['template_id' => $this->template()->id, 'parameters' => ['Awa']])
            ->assertSessionHas('error');

        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/marketing/'));
    }

    public function test_template_is_blocked_if_opt_out_cannot_be_checked(): void
    {
        Http::fake(['botisimo.test/v1/clients*' => Http::response([], 500)]);

        $this->actingAs($this->vendor)
            ->post($this->route('inbox.template', [$this->closedConversation()]), ['template_id' => $this->template()->id, 'parameters' => ['Awa']])
            ->assertSessionHas('error');

        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/marketing/'));
    }

    public function test_marketing_pages_are_forbidden_to_other_vendors(): void
    {
        Http::fake();
        [$intruder] = $this->vendorSetup('bot-2');
        $this->actingAs($intruder);

        $this->get($this->route('clients.index'))->assertForbidden();
        $this->get($this->route('templates.index'))->assertForbidden();
        $this->post($this->route('clients.store'), ['phone' => '237699000000'])->assertForbidden();

        Http::assertNothingSent();
    }
}
