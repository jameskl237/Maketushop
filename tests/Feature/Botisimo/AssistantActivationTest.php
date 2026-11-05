<?php

namespace Tests\Feature\Botisimo;

use App\Models\Shop;
use App\Models\ShopBot;
use App\Models\ShopSubscription;
use App\Models\User;
use App\Notifications\AssistantTokenExpiringNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AssistantActivationTest extends TestCase
{
    use RefreshDatabase;

    private const SETTINGS = ['tone' => 'Amical', 'language' => 'fr', 'delivery_policy' => 'Yaoundé sous 48 h', 'return_policy' => null];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        config([
            'app.url' => 'https://maketu.test',
            'botisimo.api_key' => 'bts_x',
            'botisimo.base_url' => 'https://botisimo.test',
            'botisimo.rate_limit.max_requests' => 100,
            'botisimo.retry.max_attempts' => 1,
        ]);
    }

    private function vendor(): User
    {
        return User::factory()->create(['role' => 'supplier', 'is_vendeur' => true]);
    }

    private function shop(User $vendor, ?string $plan = ShopSubscription::PLAN_STANDARD): Shop
    {
        $shop = Shop::factory()->create(['user_id' => $vendor->id, 'name' => 'Chez Awa']);

        if ($plan) {
            $shop->subscriptions()->create(['plan' => $plan, 'status' => 'active', 'ends_at' => now()->addDays(10)]);
        }

        return $shop;
    }

    private function provisionedBot(Shop $shop, array $attributes = []): ShopBot
    {
        return ShopBot::create($attributes + [
            'shop_id' => $shop->id,
            'botisimo_bot_id' => 'bot-1',
            'webhook_secret' => 'whsec',
        ]);
    }

    private function fakeProvisioning(): void
    {
        Http::fake([
            'botisimo.test/v1/bots' => Http::response(['id' => 'bot-1'], 201),
            'botisimo.test/v1/bots/bot-1' => Http::response(['ok' => true]),
            'botisimo.test/v1/webhooks' => Http::response(['secret' => 'whsec_abc'], 201),
        ]);
    }

    private function callbackUrl(ShopBot $bot, array $botisimoParams): string
    {
        $signed = URL::temporarySignedRoute('backoffice.supplier.assistant.callback', now()->addHour(), ['shopBot' => $bot->id]);

        return $signed.'&'.http_build_query($botisimoParams);
    }

    public function test_activation_creates_configures_the_bot_and_stores_the_secret_encrypted(): void
    {
        $this->fakeProvisioning();
        $vendor = $this->vendor();
        $shop = $this->shop($vendor);

        $this->actingAs($vendor)
            ->post(route('backoffice.supplier.assistant.activate', $shop), self::SETTINGS)
            ->assertSessionHas('success');

        $bot = $shop->bot;
        $this->assertSame('bot-1', $bot->botisimo_bot_id);
        $this->assertSame('whsec_abc', $bot->webhook_secret);
        $this->assertSame(ShopBot::STATUS_PENDING, $bot->status);
        $this->assertStringContainsString('Yaoundé sous 48 h', $bot->ai_instructions);
        $this->assertStringContainsString("N'invente jamais un prix", $bot->ai_instructions);

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r->url() === 'https://botisimo.test/v1/bots'
            && $r['name'] === 'Chez Awa' && $r['ai_model'] === config('botisimo.default_model'));
        Http::assertSent(fn (Request $r) => $r->method() === 'PUT' && $r['enable_human_transfer'] === true
            && $r['enable_context_memory'] === true);
        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/v1/webhooks')
            && $r['url'] === "https://maketu.test/webhooks/botisimo/{$bot->id}"
            && $r['events'] === ['message.received', 'message.status']);
    }

    public function test_failed_activation_resumes_without_creating_a_second_bot(): void
    {
        Http::fake([
            'botisimo.test/v1/bots' => Http::response(['id' => 'bot-1'], 201),
            'botisimo.test/v1/bots/bot-1' => Http::response([]),
            'botisimo.test/v1/webhooks' => Http::sequence()->push(['error' => 'x'], 500)->push(['secret' => 's2'], 201),
        ]);
        $vendor = $this->vendor();
        $shop = $this->shop($vendor);
        $url = route('backoffice.supplier.assistant.activate', $shop);

        $this->actingAs($vendor)->post($url, self::SETTINGS)->assertSessionHas('error');
        $this->assertFalse($shop->bot->isProvisioned());
        $this->assertNotNull($shop->bot->last_error);

        $this->actingAs($vendor)->post($url, self::SETTINGS)->assertSessionHas('success');

        $this->assertTrue($shop->fresh()->bot->isProvisioned());
        $this->assertNull($shop->fresh()->bot->last_error);
        Http::assertSentCount(5);
        $this->assertSame(1, collect(Http::recorded())
            ->filter(fn ($pair) => $pair[0]->method() === 'POST' && str_ends_with($pair[0]->url(), '/v1/bots'))->count());
    }

    public function test_lost_webhook_secret_is_recovered_by_recreating_the_webhook(): void
    {
        Http::fake([
            'botisimo.test/v1/bots/bot-1' => Http::response([]),
            'botisimo.test/v1/webhooks/bot-1' => Http::response([]),
            'botisimo.test/v1/webhooks' => Http::sequence()->push(['error' => 'exists'], 409)->push(['secret' => 'new'], 201),
        ]);
        $vendor = $this->vendor();
        $shop = $this->shop($vendor);
        ShopBot::create(['shop_id' => $shop->id, 'botisimo_bot_id' => 'bot-1']);

        $this->actingAs($vendor)
            ->post(route('backoffice.supplier.assistant.activate', $shop), self::SETTINGS)
            ->assertSessionHas('success');

        $this->assertSame('new', $shop->bot->fresh()->webhook_secret);
        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/v1/webhooks/bot-1'));
    }

    public function test_activation_requires_a_public_https_url(): void
    {
        config(['app.url' => 'http://localhost']);
        Http::fake();
        $vendor = $this->vendor();
        $shop = $this->shop($vendor);

        $this->actingAs($vendor)
            ->post(route('backoffice.supplier.assistant.activate', $shop), self::SETTINGS)
            ->assertSessionHas('error');

        Http::assertNothingSent();
    }

    public function test_activation_requires_a_standard_subscription(): void
    {
        Http::fake();
        $vendor = $this->vendor();

        foreach ([ShopSubscription::PLAN_FREE, null] as $plan) {
            $shop = $this->shop($vendor, $plan);

            $this->actingAs($vendor)
                ->post(route('backoffice.supplier.assistant.activate', $shop), self::SETTINGS)
                ->assertForbidden();
        }

        Http::assertNothingSent();
    }

    public function test_expired_subscription_does_not_grant_access(): void
    {
        $vendor = $this->vendor();
        $shop = $this->shop($vendor, null);
        $shop->subscriptions()->create(['plan' => 'standard', 'status' => 'active', 'ends_at' => now()->subDay()]);

        $this->assertFalse($shop->fresh()->hasAssistantAccess());
    }

    public function test_a_vendor_cannot_reach_another_vendors_assistant(): void
    {
        Http::fake();
        $owner = $this->vendor();
        $shop = $this->shop($owner);
        $this->provisionedBot($shop);
        $intruder = $this->vendor();
        $this->shop($intruder);

        $this->actingAs($intruder);
        $this->get(route('backoffice.supplier.assistant.show', $shop))->assertForbidden();
        $this->post(route('backoffice.supplier.assistant.activate', $shop), self::SETTINGS)->assertForbidden();
        $this->post(route('backoffice.supplier.assistant.connect', $shop))->assertForbidden();
        $this->post(route('backoffice.supplier.assistant.finalize', $shop))->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_non_suppliers_are_rejected(): void
    {
        $shop = $this->shop($this->vendor());
        $client = User::factory()->create(['role' => 'client']);

        $this->actingAs($client)->get(route('backoffice.supplier.assistant.show', $shop))->assertForbidden();
    }

    public function test_page_exposes_status_but_never_the_webhook_secret(): void
    {
        $vendor = $this->vendor();
        $shop = $this->shop($vendor);
        $this->provisionedBot($shop);

        $this->actingAs($vendor)
            ->get(route('backoffice.supplier.assistant.show', $shop))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Backoffice/Supplier/Assistant/Show', false)
                ->where('hasAccess', true)
                ->where('bot.provisioned', true)
                ->missing('bot.webhook_secret'))
            ->assertDontSee('whsec');
    }

    public function test_connect_redirects_to_meta_with_a_signed_callback(): void
    {
        Http::fake(['botisimo.test/v1/bots/bot-1/connect' => Http::response(['redirect_url' => 'https://meta.test/signup'])]);
        $vendor = $this->vendor();
        $shop = $this->shop($vendor);
        $bot = $this->provisionedBot($shop);

        $this->actingAs($vendor)
            ->withHeaders(['X-Inertia' => 'true'])
            ->post(route('backoffice.supplier.assistant.connect', $shop))
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', 'https://meta.test/signup');

        $this->assertSame(ShopBot::STATUS_CONNECTING, $bot->fresh()->status);
        Http::assertSent(fn (Request $r) => str_contains($r['callback_url'], "/assistant/callback/{$bot->id}")
            && str_contains($r['callback_url'], 'signature='));
    }

    public function test_callback_success_saves_the_number_and_activates_the_bot(): void
    {
        Http::fake([
            'botisimo.test/v1/bots/bot-1' => Http::response(['quality_rating' => 'GREEN', 'token_expires_at' => '2027-01-15T10:00:00Z']),
            'botisimo.test/v1/bots/bot-1/activate' => Http::response([]),
        ]);
        $vendor = $this->vendor();
        $shop = $this->shop($vendor);
        $bot = $this->provisionedBot($shop, ['status' => ShopBot::STATUS_CONNECTING, 'token_expiry_notified_at' => now()]);

        $this->actingAs($vendor)
            ->get($this->callbackUrl($bot, ['success' => 'true', 'bot_id' => 'bot-1', 'phone_number_id' => 'pn1', 'phone_number' => '237699000000']))
            ->assertRedirect(route('backoffice.supplier.assistant.show', $shop))
            ->assertSessionHas('success');

        $bot->refresh();
        $this->assertSame(ShopBot::STATUS_ACTIVE, $bot->status);
        $this->assertSame('237699000000', $bot->whatsapp_phone_number);
        $this->assertSame('pn1', $bot->phone_number_id);
        $this->assertSame('GREEN', $bot->quality_rating);
        $this->assertSame('2027-01-15', $bot->token_expires_at->toDateString());
        $this->assertNull($bot->token_expiry_notified_at);
    }

    public function test_callback_error_is_recorded_without_calling_the_api(): void
    {
        Http::fake();
        $vendor = $this->vendor();
        $shop = $this->shop($vendor);
        $bot = $this->provisionedBot($shop, ['status' => ShopBot::STATUS_CONNECTING]);

        $this->actingAs($vendor)
            ->get($this->callbackUrl($bot, ['error' => '<b>signup_cancelled</b>', 'bot_id' => 'bot-1']))
            ->assertSessionHas('error');

        $this->assertSame(ShopBot::STATUS_ERROR, $bot->fresh()->status);
        $this->assertStringContainsString('signup_cancelled', $bot->fresh()->last_error);
        $this->assertStringNotContainsString('<b>', $bot->fresh()->last_error);
        Http::assertNothingSent();
    }

    public function test_callback_rejects_a_missing_or_expired_signature(): void
    {
        Http::fake();
        $vendor = $this->vendor();
        $bot = $this->provisionedBot($this->shop($vendor));
        $query = ['success' => 'true', 'bot_id' => 'bot-1'];

        $this->actingAs($vendor)
            ->get(route('backoffice.supplier.assistant.callback', ['shopBot' => $bot->id] + $query))
            ->assertForbidden();

        $expired = URL::temporarySignedRoute('backoffice.supplier.assistant.callback', now()->subMinute(), ['shopBot' => $bot->id]);
        $this->actingAs($vendor)->get($expired.'&'.http_build_query($query))->assertForbidden();

        $this->assertSame(ShopBot::STATUS_PENDING, $bot->fresh()->status);
    }

    public function test_callback_signature_cannot_be_reused_for_another_bot(): void
    {
        Http::fake();
        $vendor = $this->vendor();
        $bot = $this->provisionedBot($this->shop($vendor));
        $other = ShopBot::create(['shop_id' => $this->shop($vendor)->id, 'botisimo_bot_id' => 'bot-2']);

        $url = str_replace("/callback/{$bot->id}?", "/callback/{$other->id}?", $this->callbackUrl($bot, ['success' => 'true', 'bot_id' => 'bot-2']));

        $this->actingAs($vendor)->get($url)->assertForbidden();
    }

    public function test_callback_rejects_a_bot_id_mismatch_and_other_vendors(): void
    {
        Http::fake();
        $vendor = $this->vendor();
        $bot = $this->provisionedBot($this->shop($vendor));

        $this->actingAs($vendor)
            ->get($this->callbackUrl($bot, ['success' => 'true', 'bot_id' => 'autre']))
            ->assertForbidden();

        $this->actingAs($this->vendor())
            ->get($this->callbackUrl($bot, ['success' => 'true', 'bot_id' => 'bot-1']))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_finalize_recovers_when_activation_failed_after_meta(): void
    {
        Http::fake([
            'botisimo.test/v1/bots/bot-1' => Http::response([]),
            'botisimo.test/v1/bots/bot-1/activate' => Http::sequence()->push([], 500)->push([]),
        ]);
        $vendor = $this->vendor();
        $shop = $this->shop($vendor);
        $bot = $this->provisionedBot($shop, ['status' => ShopBot::STATUS_CONNECTING]);

        $this->actingAs($vendor)
            ->get($this->callbackUrl($bot, ['success' => 'true', 'bot_id' => 'bot-1', 'phone_number_id' => 'pn1', 'phone_number' => '2376']))
            ->assertSessionHas('error');

        $bot->refresh();
        $this->assertSame(ShopBot::STATUS_ERROR, $bot->status);
        $this->assertSame('pn1', $bot->phone_number_id);

        $this->actingAs($vendor)
            ->post(route('backoffice.supplier.assistant.finalize', $shop))
            ->assertSessionHas('success');

        $this->assertSame(ShopBot::STATUS_ACTIVE, $bot->fresh()->status);
    }

    public function test_token_expiry_command_warns_once(): void
    {
        Notification::fake();
        $vendor = $this->vendor();
        $this->provisionedBot($this->shop($vendor), ['status' => ShopBot::STATUS_ACTIVE, 'token_expires_at' => now()->addDays(3)]);
        ShopBot::create(['shop_id' => $this->shop($vendor)->id, 'status' => ShopBot::STATUS_ACTIVE, 'token_expires_at' => now()->addDays(30)]);

        $this->artisan('botisimo:check-token-expiry')->assertSuccessful();
        $this->artisan('botisimo:check-token-expiry')->assertSuccessful();

        Notification::assertSentToTimes($vendor, AssistantTokenExpiringNotification::class, 1);
    }
}
