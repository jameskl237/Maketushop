<?php

namespace Tests\Feature\Botisimo;

use App\Jobs\Botisimo\DeleteRemoteBot;
use App\Models\Shop;
use App\Models\ShopBot;
use App\Models\ShopBotCreditUsage;
use App\Models\User;
use App\Notifications\AssistantLowBalanceNotification;
use App\Notifications\AssistantSuspendedNotification;
use App\Services\Botisimo\AssistantProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AssistantManagementTest extends TestCase
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
            'botisimo.rate_limit.max_requests' => 1000,
            'botisimo.retry.max_attempts' => 1,
            'botisimo.credits.low_balance_threshold' => 1000,
        ]);

        [$this->vendor, $this->shop, $this->bot] = $this->vendorSetup('bot-1');
    }

    protected function tearDown(): void
    {
        Cache::flush();

        parent::tearDown();
    }

    private function vendorSetup(string $botId, bool $standard = true): array
    {
        $vendor = User::factory()->create(['role' => 'supplier']);
        $shop = Shop::factory()->create(['user_id' => $vendor->id, 'name' => 'Chez Awa']);

        if ($standard) {
            $shop->subscriptions()->create(['plan' => 'standard', 'status' => 'active', 'ends_at' => now()->addDays(5)]);
        }

        $bot = ShopBot::create([
            'shop_id' => $shop->id, 'botisimo_bot_id' => $botId, 'webhook_secret' => 's',
            'phone_number_id' => 'pn', 'status' => ShopBot::STATUS_ACTIVE, 'ai_model' => 'gpt-6-luna',
        ]);

        return [$vendor, $shop, $bot];
    }

    private function settings(array $overrides = []): array
    {
        return $overrides + [
            'additional_instructions' => 'Proposer la livraison à Douala.',
            'ai_temperature' => 0.4,
            'tone' => 'Amical',
            'language' => 'fr',
            'delivery_policy' => 'Sous 48 h',
            'return_policy' => null,
            'welcome_message_enabled' => true,
            'welcome_message' => 'Bienvenue !',
            'away_message_enabled' => false,
            'away_message' => null,
            'human_transfer_keywords' => ['réclamation', 'plainte'],
            'business_address' => 'Akwa, Douala',
            'ai_share_location' => true,
        ];
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    // --- Paramètres ---

    public function test_settings_are_pushed_with_regenerated_instructions(): void
    {
        Http::fake(['botisimo.test/v1/bots/bot-1' => Http::response([])]);

        $this->actingAs($this->vendor)
            ->put(route('backoffice.supplier.assistant.settings.update', $this->shop), $this->settings())
            ->assertSessionHas('success');

        $bot = $this->bot->fresh();
        $this->assertSame(0.4, $bot->ai_temperature);
        $this->assertStringContainsString('Proposer la livraison à Douala.', $bot->ai_instructions);
        $this->assertStringContainsString('Sous 48 h', $bot->ai_instructions);

        Http::assertSent(fn (Request $r) => $r->method() === 'PUT'
            && $r['ai_temperature'] === 0.4
            && $r['welcome_message'] === 'Bienvenue !'
            && $r['human_transfer_keywords'] === ['réclamation', 'plainte']
            && $r['business_address'] === 'Akwa, Douala'
            && $r['ai_share_location'] === true
            && str_contains($r['ai_instructions'], 'Douala')
            && ! isset($r['ai_model']));
    }

    public function test_clearing_a_text_field_really_clears_it_remotely(): void
    {
        Http::fake(['*' => Http::response([])]);
        $this->bot->update(['business_address' => 'Ancienne adresse']);

        $this->actingAs($this->vendor)
            ->put(route('backoffice.supplier.assistant.settings.update', $this->shop), $this->settings(['business_address' => null]))
            ->assertSessionHas('success');

        Http::assertSent(fn (Request $r) => $r['business_address'] === '');
    }

    public function test_rejected_settings_stay_unsaved_locally(): void
    {
        Http::fake(['*' => Http::response([], 500)]);

        $this->actingAs($this->vendor)
            ->put(route('backoffice.supplier.assistant.settings.update', $this->shop), $this->settings(['tone' => 'Nouveau']))
            ->assertSessionHas('error');

        $this->assertNull($this->bot->fresh()->tone);
    }

    public function test_settings_validation(): void
    {
        Http::fake();
        $this->actingAs($this->vendor);
        $url = route('backoffice.supplier.assistant.settings.update', $this->shop);

        $this->put($url, $this->settings(['ai_temperature' => 3]))->assertSessionHasErrors('ai_temperature');
        $this->put($url, $this->settings(['away_message_enabled' => true]))->assertSessionHasErrors('away_message');
        $this->put($url, $this->settings(['language' => 'de']))->assertSessionHasErrors('language');

        Http::assertNothingSent();
    }

    public function test_page_shows_the_model_as_information_only(): void
    {
        $this->actingAs($this->vendor)
            ->get(route('backoffice.supplier.assistant.settings.edit', $this->shop))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Backoffice/Supplier/Assistant/Settings', false)
                ->where('settings.ai_model', 'gpt-6-luna')
                ->missing('settings.webhook_secret'));
    }

    // --- Activation, désactivation, suppression ---

    public function test_deactivating_and_reactivating(): void
    {
        Http::fake(['*' => Http::response([])]);
        $this->actingAs($this->vendor);
        $url = route('backoffice.supplier.assistant.status', $this->shop);

        $this->post($url, ['active' => false])->assertSessionHas('success');
        $this->assertSame(ShopBot::STATUS_INACTIVE, $this->bot->fresh()->status);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/v1/bots/bot-1/deactivate'));

        $this->post($url, ['active' => true])->assertSessionHas('success');
        $this->assertSame(ShopBot::STATUS_ACTIVE, $this->bot->fresh()->status);
    }

    public function test_without_subscription_a_vendor_can_stop_but_not_restart(): void
    {
        Http::fake(['*' => Http::response([])]);
        [$vendor, $shop, $bot] = $this->vendorSetup('bot-2', standard: false);
        $this->actingAs($vendor);
        $url = route('backoffice.supplier.assistant.status', $shop);

        $this->post($url, ['active' => false])->assertSessionHas('success');
        $this->post($url, ['active' => true])->assertForbidden();

        $this->assertSame(ShopBot::STATUS_INACTIVE, $bot->fresh()->status);
    }

    public function test_deletion_needs_the_exact_shop_name(): void
    {
        Http::fake();
        $this->actingAs($this->vendor);

        $this->delete(route('backoffice.supplier.assistant.destroy', $this->shop), ['confirmation' => 'chez awa'])
            ->assertSessionHasErrors('confirmation');
        $this->delete(route('backoffice.supplier.assistant.destroy', $this->shop))
            ->assertSessionHasErrors('confirmation');

        Http::assertNothingSent();
        $this->assertModelExists($this->bot);
    }

    public function test_deletion_deactivates_then_deletes_and_keeps_the_credit_ledger(): void
    {
        Http::fake(['*' => Http::response([])]);
        ShopBotCreditUsage::create(['shop_id' => $this->shop->id, 'action' => 'message', 'credits' => 10]);
        $this->bot->conversations()->create(['botisimo_conversation_id' => 'c1']);

        $this->actingAs($this->vendor)
            ->delete(route('backoffice.supplier.assistant.destroy', $this->shop), ['confirmation' => 'Chez Awa'])
            ->assertRedirect(route('backoffice.supplier.assistant.show', $this->shop));

        $calls = collect(Http::recorded())->map(fn ($pair) => $pair[0]->method().' '.parse_url($pair[0]->url(), PHP_URL_PATH))->all();
        $this->assertSame(['POST /v1/bots/bot-1/deactivate', 'DELETE /v1/bots/bot-1'], $calls);
        $this->assertModelMissing($this->bot);
        $this->assertDatabaseCount('shop_bot_conversations', 0);
        $this->assertDatabaseCount('shop_bot_credit_usages', 1);
    }

    public function test_failed_remote_deletion_keeps_the_local_bot(): void
    {
        Http::fake(['*' => Http::response([], 500)]);

        $this->actingAs($this->vendor)
            ->delete(route('backoffice.supplier.assistant.destroy', $this->shop), ['confirmation' => 'Chez Awa'])
            ->assertSessionHas('error');

        $this->assertModelExists($this->bot);
    }

    public function test_bot_already_gone_remotely_is_removed_locally(): void
    {
        Http::fake(['*' => Http::response([], 404)]);

        $this->actingAs($this->vendor)
            ->delete(route('backoffice.supplier.assistant.destroy', $this->shop), ['confirmation' => 'Chez Awa'])
            ->assertSessionHas('success');

        $this->assertModelMissing($this->bot);
    }

    public function test_deleting_a_shop_removes_its_remote_bot(): void
    {
        Bus::fake([DeleteRemoteBot::class]);

        $this->shop->delete();

        Bus::assertDispatched(DeleteRemoteBot::class, fn ($job) => $job->botisimoBotId === 'bot-1');
    }

    public function test_deleting_a_vendor_removes_the_remote_bots_of_all_its_shops(): void
    {
        Bus::fake([DeleteRemoteBot::class]);
        $second = Shop::factory()->create(['user_id' => $this->vendor->id]);
        ShopBot::create(['shop_id' => $second->id, 'botisimo_bot_id' => 'bot-3']);

        $this->vendor->delete();

        Bus::assertDispatchedTimes(DeleteRemoteBot::class, 2);
    }

    public function test_remote_deletion_job_calls_the_api(): void
    {
        Http::fake(['*' => Http::response([])]);

        (new DeleteRemoteBot('bot-1'))->handle(app(AssistantProvisioner::class));

        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/v1/bots/bot-1'));
    }

    public function test_management_routes_are_isolated(): void
    {
        Http::fake();
        [$intruder] = $this->vendorSetup('bot-2');
        $this->actingAs($intruder);

        $this->get(route('backoffice.supplier.assistant.settings.edit', $this->shop))->assertForbidden();
        $this->put(route('backoffice.supplier.assistant.settings.update', $this->shop), $this->settings())->assertForbidden();
        $this->post(route('backoffice.supplier.assistant.status', $this->shop), ['active' => false])->assertForbidden();
        $this->delete(route('backoffice.supplier.assistant.destroy', $this->shop), ['confirmation' => 'Chez Awa'])->assertForbidden();
        $this->get(route('backoffice.supplier.assistant.stats', $this->shop))->assertForbidden();

        Http::assertNothingSent();
        $this->assertModelExists($this->bot);
    }

    // --- Abonnement expiré ---

    public function test_expired_subscription_deactivates_the_bot_once(): void
    {
        Http::fake(['*' => Http::response([])]);
        Notification::fake();
        $this->shop->subscriptions()->update(['ends_at' => now()->subDay()]);
        [, , $paying] = $this->vendorSetup('bot-2');

        $this->artisan('botisimo:enforce-subscriptions')->assertSuccessful();
        $this->artisan('botisimo:enforce-subscriptions')->assertSuccessful();

        $this->assertSame(ShopBot::STATUS_INACTIVE, $this->bot->fresh()->status);
        $this->assertSame(ShopBot::STATUS_ACTIVE, $paying->fresh()->status);
        Http::assertSentCount(1);
        Notification::assertSentToTimes($this->vendor, AssistantSuspendedNotification::class, 1);
    }

    public function test_failed_deactivation_is_retried_next_run(): void
    {
        Http::fakeSequence()->push([], 500)->push([]);
        Notification::fake();
        $this->shop->subscriptions()->update(['ends_at' => now()->subDay()]);

        $this->artisan('botisimo:enforce-subscriptions')->assertSuccessful();
        $this->assertSame(ShopBot::STATUS_ACTIVE, $this->bot->fresh()->status);

        $this->artisan('botisimo:enforce-subscriptions')->assertSuccessful();
        $this->assertSame(ShopBot::STATUS_INACTIVE, $this->bot->fresh()->status);
    }

    // --- Statistiques ---

    public function test_stats_sum_the_shops_own_activity(): void
    {
        $conversation = $this->bot->conversations()->create([
            'botisimo_conversation_id' => 'c1', 'client_phone' => '2376', 'last_message_at' => now(),
        ]);
        $this->bot->conversations()->create([
            'botisimo_conversation_id' => 'c2', 'client_phone' => '2376', 'last_message_at' => now()->subDays(60),
        ]);
        $conversation->messages()->create(['message_id' => 'a', 'direction' => 'inbound', 'sent_at' => now()]);
        $conversation->messages()->create(['message_id' => 'b', 'direction' => 'outbound', 'sent_at' => now()]);
        $conversation->messages()->create(['message_id' => 'c', 'direction' => 'outbound', 'sent_at' => now()]);
        $this->bot->campaigns()->create(['template_name' => 't', 'status' => 'sent', 'sent' => 12]);
        ShopBotCreditUsage::create(['shop_id' => $this->shop->id, 'action' => 'broadcast', 'credits' => 12]);
        $old = ShopBotCreditUsage::create(['shop_id' => $this->shop->id, 'action' => 'message', 'credits' => 10]);
        $old->forceFill(['created_at' => now()->subMonths(2)])->save();

        [, $otherShop, $otherBot] = $this->vendorSetup('bot-2');
        $otherBot->conversations()->create(['botisimo_conversation_id' => 'x', 'client_phone' => '2377']);
        ShopBotCreditUsage::create(['shop_id' => $otherShop->id, 'action' => 'message', 'credits' => 500]);

        $this->actingAs($this->vendor)
            ->get(route('backoffice.supplier.assistant.stats', $this->shop))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('stats.conversations', 2)
                ->where('stats.conversations_30d', 1)
                ->where('stats.clients', 1)
                ->where('stats.messages_inbound', 1)
                ->where('stats.messages_outbound', 2)
                ->where('stats.campaigns', 1)
                ->where('stats.campaign_recipients', 12)
                ->where('stats.credits_month', 12)
                ->where('stats.credits_total', 22)
                ->where('stats.credits_quota', config('botisimo.credits.monthly_quota')));
    }

    // --- Administration ---

    public function test_admin_sees_all_bots_balance_and_consumption(): void
    {
        Http::fake(['botisimo.test/v1/credits/balance' => Http::response(['balance' => 800])]);
        ShopBotCreditUsage::create(['shop_id' => $this->shop->id, 'action' => 'message', 'credits' => 30]);
        $this->bot->update(['last_error' => 'Problème']);
        $this->bot->catalogItems()->create(['catalogable_type' => 'product', 'catalogable_id' => 1, 'sync_error' => 'x']);
        $this->vendorSetup('bot-2');

        $this->actingAs($this->admin())
            ->get(route('backoffice.admin.assistant.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Backoffice/Admin/Assistant/Index', false)
                ->where('balance', 800)
                ->where('creditsThisMonth', 30)
                ->has('bots.data', 2)
                ->where('bots.data.1.credits_month', 30)
                ->where('bots.data.1.sync_errors_count', 1)
                ->where('bots.data.1.last_error', 'Problème')
                ->missing('bots.data.0.webhook_secret'));
    }

    public function test_admin_page_survives_an_unreachable_balance(): void
    {
        Http::fake(['*' => Http::response([], 500)]);

        $this->actingAs($this->admin())
            ->get(route('backoffice.admin.assistant.index'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('balance', null));
    }

    public function test_recharge_sends_the_request_once(): void
    {
        Http::fake(['botisimo.test/v1/credits/recharge' => Http::response(['status' => 'pending'])]);
        $this->actingAs($this->admin());
        $payload = ['amount' => 5000, 'operator' => 'mtn', 'phone_number' => '+237 699 00 00 00'];

        $this->post(route('backoffice.admin.assistant.recharge'), $payload)->assertSessionHas('success');
        $this->post(route('backoffice.admin.assistant.recharge'), $payload)->assertSessionHas('error');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $r) => $r['amount'] === 5000 && $r['operator'] === 'mtn'
            && $r['phone_number'] === '237699000000' && $r['country_code'] === 'CM' && $r['currency'] === 'XAF'
            && filled($r['callback_url']));
    }

    public function test_failed_recharge_can_be_retried_immediately(): void
    {
        Http::fakeSequence()->push([], 400)->push([]);
        $this->actingAs($this->admin());
        $payload = ['amount' => 5000, 'operator' => 'orange', 'phone_number' => '237699000000'];

        $this->post(route('backoffice.admin.assistant.recharge'), $payload)->assertSessionHas('error');
        $this->post(route('backoffice.admin.assistant.recharge'), $payload)->assertSessionHas('success');
    }

    public function test_recharge_validation(): void
    {
        Http::fake();
        $this->actingAs($this->admin());

        $this->post(route('backoffice.admin.assistant.recharge'), ['amount' => 499, 'operator' => 'mtn', 'phone_number' => '237699000000'])->assertSessionHasErrors('amount');
        $this->post(route('backoffice.admin.assistant.recharge'), ['amount' => 500, 'operator' => 'free', 'phone_number' => '237699000000'])->assertSessionHasErrors('operator');

        Http::assertNothingSent();
    }

    public function test_quota_override_applies_to_the_shop(): void
    {
        $this->actingAs($this->admin())
            ->patch(route('backoffice.admin.assistant.quota', $this->bot), ['monthly_credit_quota' => 2500])
            ->assertSessionHas('success');
        $this->assertSame(2500, $this->bot->fresh()->monthly_credit_quota);

        $this->actingAs($this->admin())
            ->patch(route('backoffice.admin.assistant.quota', $this->bot), ['monthly_credit_quota' => null]);
        $this->assertNull($this->bot->fresh()->monthly_credit_quota);
    }

    public function test_vendors_cannot_reach_the_admin_page(): void
    {
        Http::fake();
        $this->actingAs($this->vendor);

        $this->get(route('backoffice.admin.assistant.index'))->assertForbidden();
        $this->post(route('backoffice.admin.assistant.recharge'), ['amount' => 500, 'operator' => 'mtn', 'phone_number' => '237699000000'])->assertForbidden();
        $this->patch(route('backoffice.admin.assistant.quota', $this->bot), ['monthly_credit_quota' => 999999])->assertForbidden();

        Http::assertNothingSent();
        $this->assertNull($this->bot->fresh()->monthly_credit_quota);
    }

    public function test_low_balance_alerts_admins_at_most_once_a_day(): void
    {
        Http::fake(['*' => Http::response(['balance' => 100])]);
        Notification::fake();
        $admin = $this->admin();

        $this->artisan('botisimo:check-balance')->assertSuccessful();
        $this->artisan('botisimo:check-balance')->assertSuccessful();

        Notification::assertSentToTimes($admin, AssistantLowBalanceNotification::class, 1);
        Notification::assertNotSentTo($this->vendor, AssistantLowBalanceNotification::class);
    }

    public function test_no_alert_when_the_balance_is_healthy(): void
    {
        Notification::fake();
        $this->admin();

        Http::fake(['*' => Http::response(['balance' => 5000])]);
        $this->artisan('botisimo:check-balance')->assertSuccessful();

        Notification::assertNothingSent();
    }
}
