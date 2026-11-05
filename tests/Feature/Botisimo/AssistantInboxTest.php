<?php

namespace Tests\Feature\Botisimo;

use App\Jobs\Botisimo\ReconcileConversations;
use App\Models\Shop;
use App\Models\ShopBot;
use App\Models\ShopBotConversation;
use App\Models\ShopBotCreditUsage;
use App\Models\ShopBotMessage;
use App\Models\User;
use App\Services\Botisimo\InboxStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AssistantInboxTest extends TestCase
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
            'botisimo.credits.monthly_quota' => 100,
        ]);

        [$this->vendor, $this->shop, $this->bot] = $this->vendorSetup('bot-1');
    }

    private function vendorSetup(string $botId, bool $standard = true): array
    {
        $vendor = User::factory()->create(['role' => 'supplier']);
        $shop = Shop::factory()->create(['user_id' => $vendor->id]);

        if ($standard) {
            $shop->subscriptions()->create(['plan' => 'standard', 'status' => 'active', 'ends_at' => now()->addDays(5)]);
        }

        $bot = ShopBot::create([
            'shop_id' => $shop->id,
            'botisimo_bot_id' => $botId,
            'webhook_secret' => 's',
            'phone_number_id' => 'pn',
            'status' => ShopBot::STATUS_ACTIVE,
        ]);

        return [$vendor, $shop, $bot];
    }

    private function conversation(?ShopBot $bot = null, array $attributes = []): ShopBotConversation
    {
        return ($bot ?? $this->bot)->conversations()->create($attributes + [
            'botisimo_conversation_id' => 'c1',
            'client_phone' => '237699000000',
            'client_name' => 'Awa',
            'last_inbound_at' => now()->subHour(),
            'last_message_at' => now()->subHour(),
        ]);
    }

    private function fakeSend(): void
    {
        Http::fake([
            'botisimo.test/v1/messages/*' => Http::response(['message_id' => 'sent-1', 'meta_message_id' => 'wamid.9']),
            'botisimo.test/v1/credits/balance' => Http::response(['balance' => 5000]),
        ]);
    }

    private function reply(ShopBotConversation $conversation, string $text = 'Oui, disponible !')
    {
        return $this->actingAs($this->vendor)->post(
            route('backoffice.supplier.assistant.inbox.reply', [$this->shop, $conversation]),
            ['message' => $text],
        );
    }

    public function test_inbox_lists_only_this_shops_conversations(): void
    {
        $this->conversation();
        [, , $otherBot] = $this->vendorSetup('bot-2');
        $this->conversation($otherBot, ['client_name' => 'Autre client']);

        $this->actingAs($this->vendor)
            ->get(route('backoffice.supplier.assistant.inbox.index', $this->shop))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Backoffice/Supplier/Assistant/Inbox', false)
                ->has('conversations.data', 1)
                ->where('conversations.data.0.client_name', 'Awa'));
    }

    public function test_unconnected_shop_is_sent_back_to_activation(): void
    {
        $this->bot->update(['phone_number_id' => null]);

        $this->actingAs($this->vendor)
            ->get(route('backoffice.supplier.assistant.inbox.index', $this->shop))
            ->assertRedirect(route('backoffice.supplier.assistant.show', $this->shop));
    }

    public function test_opening_a_conversation_fetches_ai_replies_and_clears_unread(): void
    {
        $conversation = $this->conversation(attributes: ['unread_count' => 3]);
        $conversation->messages()->create(['message_id' => 'in-1', 'direction' => 'inbound', 'body' => 'Bonjour', 'sent_at' => now()->subMinutes(5)]);
        Http::fake(['botisimo.test/v1/conversations/c1/messages' => Http::response(['data' => [
            ['id' => 'in-1', 'role' => 'user', 'content' => 'Bonjour', 'created_at' => now()->subMinutes(5)->toIso8601String()],
            ['id' => 'ai-1', 'role' => 'assistant', 'content' => 'Bonjour ! En quoi puis-je aider ?', 'created_at' => now()->subMinutes(4)->toIso8601String()],
        ]])]);

        $this->actingAs($this->vendor)
            ->get(route('backoffice.supplier.assistant.inbox.show', [$this->shop, $conversation]))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('messages.data', 2)
                ->where('messages.data.0.direction', 'outbound')
                ->where('messages.data.0.body', 'Bonjour ! En quoi puis-je aider ?')
                ->where('conversation.unread_count', 0));

        $this->assertSame('Bonjour ! En quoi puis-je aider ?', $conversation->fresh()->last_message_preview);
    }

    public function test_polling_does_not_hit_the_api_on_every_request(): void
    {
        $conversation = $this->conversation();
        Http::fake(['*' => Http::response(['data' => []])]);
        $url = route('backoffice.supplier.assistant.inbox.show', [$this->shop, $conversation]);

        $this->actingAs($this->vendor)->get($url)->assertOk();
        $this->actingAs($this->vendor)->get($url)->assertOk();

        Http::assertSentCount(1);
    }

    public function test_page_still_opens_when_botisimo_is_down(): void
    {
        $conversation = $this->conversation();
        Http::fake(['*' => Http::response([], 500)]);

        $this->actingAs($this->vendor)
            ->get(route('backoffice.supplier.assistant.inbox.show', [$this->shop, $conversation]))
            ->assertOk();
    }

    public function test_reply_sends_the_message_records_it_and_charges_the_shop(): void
    {
        $this->fakeSend();
        $conversation = $this->conversation();

        $this->reply($conversation)->assertSessionHasNoErrors()->assertSessionMissing('error');

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/v1/messages/bot-1/send')
            && $r['to'] === '237699000000' && $r['message'] === 'Oui, disponible !' && $r['type'] === 'text');

        $message = ShopBotMessage::firstOrFail();
        $this->assertSame('outbound', $message->direction);
        $this->assertSame('sent-1', $message->message_id);
        $this->assertSame('Oui, disponible !', $conversation->fresh()->last_message_preview);

        $usage = ShopBotCreditUsage::firstOrFail();
        $this->assertSame($this->shop->id, $usage->shop_id);
        $this->assertSame(10, $usage->credits);
        $this->assertSame('message', $usage->action);
    }

    public function test_reply_outside_the_24_hour_window_is_blocked(): void
    {
        Http::fake();
        $conversation = $this->conversation(attributes: ['last_inbound_at' => now()->subHours(25)]);

        $this->reply($conversation)->assertSessionHas('error');

        Http::assertNothingSent();
        $this->assertDatabaseCount('shop_bot_credit_usages', 0);
    }

    public function test_reply_is_blocked_when_the_quota_is_spent(): void
    {
        $this->fakeSend();
        ShopBotCreditUsage::create(['shop_id' => $this->shop->id, 'action' => 'message', 'credits' => 95]);

        $this->reply($this->conversation())->assertSessionHas('error');

        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/messages/'));
    }

    public function test_quota_can_be_raised_per_shop_and_resets_each_month(): void
    {
        $this->fakeSend();
        $old = ShopBotCreditUsage::create(['shop_id' => $this->shop->id, 'action' => 'message', 'credits' => 100]);
        $old->forceFill(['created_at' => now()->subMonths(2)])->save();

        $this->reply($this->conversation())->assertSessionMissing('error');

        ShopBotCreditUsage::create(['shop_id' => $this->shop->id, 'action' => 'message', 'credits' => 90]);
        $this->bot->update(['monthly_credit_quota' => 1000]);

        $this->reply($this->conversation(attributes: ['botisimo_conversation_id' => 'c2']))->assertSessionMissing('error');
        $this->assertDatabaseCount('shop_bot_messages', 2);
    }

    public function test_reply_is_blocked_when_the_platform_balance_is_empty(): void
    {
        Http::fake(['botisimo.test/v1/credits/balance' => Http::response(['balance' => 5])]);

        $this->reply($this->conversation())->assertSessionHas('error');

        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), '/messages/'));
    }

    public function test_failed_send_costs_nothing(): void
    {
        Http::fake([
            'botisimo.test/v1/credits/balance' => Http::response(['balance' => 5000]),
            'botisimo.test/v1/messages/*' => Http::response(['error' => 'meta'], 502),
        ]);

        $this->reply($this->conversation())->assertSessionHas('error');

        $this->assertDatabaseCount('shop_bot_credit_usages', 0);
        $this->assertDatabaseCount('shop_bot_messages', 0);
    }

    public function test_sent_reply_without_id_is_not_duplicated_when_refreshing(): void
    {
        Http::fake([
            'botisimo.test/v1/credits/balance' => Http::response(['balance' => 5000]),
            'botisimo.test/v1/messages/*' => Http::response([]),
            'botisimo.test/v1/conversations/c1/messages' => Http::response(['data' => [
                ['id' => 'remote-1', 'role' => 'agent', 'content' => 'Oui, disponible !'],
            ]]),
        ]);
        $conversation = $this->conversation();

        $this->reply($conversation);
        $this->actingAs($this->vendor)->get(route('backoffice.supplier.assistant.inbox.show', [$this->shop, $conversation]));

        $this->assertDatabaseCount('shop_bot_messages', 1);
        $this->assertSame('remote-1', ShopBotMessage::firstOrFail()->message_id);
    }

    public function test_taking_over_pauses_the_ai_and_handing_back_resumes_it(): void
    {
        Http::fake(['botisimo.test/v1/conversations/c1/ai' => Http::response([])]);
        $conversation = $this->conversation();
        $url = route('backoffice.supplier.assistant.inbox.ai', [$this->shop, $conversation]);

        $this->actingAs($this->vendor)->put($url, ['ai_enabled' => false]);
        $this->assertFalse($conversation->fresh()->ai_enabled);
        Http::assertSent(fn (Request $r) => $r->method() === 'PUT' && $r['ai_enabled'] === false);

        $this->actingAs($this->vendor)->put($url, ['ai_enabled' => true]);
        $this->assertTrue($conversation->fresh()->ai_enabled);
    }

    public function test_failed_toggle_keeps_the_local_state(): void
    {
        Http::fake(['*' => Http::response([], 500)]);
        $conversation = $this->conversation();

        $this->actingAs($this->vendor)
            ->put(route('backoffice.supplier.assistant.inbox.ai', [$this->shop, $conversation]), ['ai_enabled' => false])
            ->assertSessionHas('error');

        $this->assertTrue($conversation->fresh()->ai_enabled);
    }

    public function test_vendors_are_isolated(): void
    {
        Http::fake();
        $conversation = $this->conversation();
        [$intruder, $intruderShop] = $this->vendorSetup('bot-2');
        $this->actingAs($intruder);

        $this->get(route('backoffice.supplier.assistant.inbox.index', $this->shop))->assertForbidden();
        $this->get(route('backoffice.supplier.assistant.inbox.show', [$this->shop, $conversation]))->assertForbidden();
        $this->put(route('backoffice.supplier.assistant.inbox.ai', [$this->shop, $conversation]), ['ai_enabled' => false])->assertForbidden();
        $this->post(route('backoffice.supplier.assistant.inbox.reply', [$this->shop, $conversation]), ['message' => 'x'])->assertForbidden();

        // Sa propre boutique dans l'URL, avec la conversation d'un autre.
        $this->get(route('backoffice.supplier.assistant.inbox.show', [$intruderShop, $conversation]))->assertNotFound();
        $this->put(route('backoffice.supplier.assistant.inbox.ai', [$intruderShop, $conversation]), ['ai_enabled' => false])->assertNotFound();
        $this->post(route('backoffice.supplier.assistant.inbox.reply', [$intruderShop, $conversation]), ['message' => 'x'])->assertNotFound();

        Http::assertNothingSent();
        $this->assertTrue($conversation->fresh()->ai_enabled);
    }

    public function test_expired_subscription_can_read_but_not_act(): void
    {
        Http::fake();
        [$vendor, $shop, $bot] = $this->vendorSetup('bot-3', standard: false);
        $conversation = $this->conversation($bot);
        $this->actingAs($vendor);

        $this->get(route('backoffice.supplier.assistant.inbox.index', $shop))->assertOk();
        $this->post(route('backoffice.supplier.assistant.inbox.reply', [$shop, $conversation]), ['message' => 'x'])->assertForbidden();
        $this->put(route('backoffice.supplier.assistant.inbox.ai', [$shop, $conversation]), ['ai_enabled' => false])->assertForbidden();
    }

    public function test_reconciliation_recovers_missed_conversations(): void
    {
        $existing = $this->conversation();
        Http::fake(['botisimo.test/v1/conversations*' => Http::response(['conversations' => [
            ['id' => 'c1', 'ai_enabled' => false, 'status' => 'closed'],
            ['id' => 'c2', 'client_phone' => '2376', 'client_name' => 'Binta'],
        ]])]);

        (new ReconcileConversations($this->bot->id))->handle(app(InboxStore::class));

        $this->assertDatabaseCount('shop_bot_conversations', 2);
        $this->assertFalse($existing->fresh()->ai_enabled);
        $this->assertSame('closed', $existing->fresh()->status);
        $this->assertSame('237699000000', $existing->fresh()->client_phone);
        $this->assertDatabaseHas('shop_bot_conversations', ['botisimo_conversation_id' => 'c2', 'client_name' => 'Binta']);
    }

    public function test_reconciliation_ignores_an_unreadable_response(): void
    {
        Http::fake(['*' => Http::response(['result' => 'ok'])]);

        (new ReconcileConversations($this->bot->id))->handle(app(InboxStore::class));

        $this->assertDatabaseCount('shop_bot_conversations', 0);
    }

    public function test_command_queues_only_active_bots(): void
    {
        Bus::fake();
        $this->vendorSetup('bot-2')[2]->update(['status' => ShopBot::STATUS_INACTIVE]);

        $this->artisan('botisimo:sync-conversations')->assertSuccessful();

        Bus::assertDispatchedTimes(ReconcileConversations::class, 1);
    }

    protected function tearDown(): void
    {
        Cache::flush();

        parent::tearDown();
    }
}
