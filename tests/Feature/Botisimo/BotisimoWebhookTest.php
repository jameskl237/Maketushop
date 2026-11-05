<?php

namespace Tests\Feature\Botisimo;

use App\Jobs\Botisimo\ProcessBotisimoWebhook;
use App\Models\Shop;
use App\Models\ShopBot;
use App\Models\ShopBotConversation;
use App\Models\ShopBotMessage;
use App\Models\ShopBotWebhookEvent;
use App\Models\User;
use App\Notifications\AssistantHumanTransferNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BotisimoWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'whsec_test';

    private User $vendor;

    private ShopBot $bot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendor = User::factory()->create(['role' => 'supplier']);
        $shop = Shop::factory()->create(['user_id' => $this->vendor->id]);
        $this->bot = ShopBot::create([
            'shop_id' => $shop->id,
            'botisimo_bot_id' => 'bot-1',
            'webhook_secret' => self::SECRET,
            'human_transfer_keywords' => ['réclamation', 'remboursement'],
        ]);
    }

    private function send(array $payload, ?string $secret = self::SECRET, ?ShopBot $bot = null)
    {
        $raw = json_encode($payload);
        $headers = ['CONTENT_TYPE' => 'application/json'];

        if ($secret !== null) {
            $headers['HTTP_X_BOTISIMO_SIGNATURE'] = 'sha256='.hash_hmac('sha256', $raw, $secret);
        }

        return $this->call('POST', route('webhooks.botisimo', $bot ?? $this->bot), [], [], [], $headers, $raw);
    }

    private function received(array $overrides = []): array
    {
        return $overrides + [
            'event' => 'message.received',
            'message_id' => 'm1',
            'meta_message_id' => 'wamid.1',
            'conversation_id' => 'c1',
            'from' => '237699000000',
            'contact_name' => 'Awa',
            'content' => 'Bonjour, le sac est disponible ?',
        ];
    }

    public function test_valid_signature_stores_the_event_and_answers_200(): void
    {
        Bus::fake();

        $this->send($this->received())->assertOk();

        $this->assertDatabaseHas('shop_bot_webhook_events', ['event' => 'message.received', 'shop_bot_id' => $this->bot->id]);
        Bus::assertDispatched(ProcessBotisimoWebhook::class);
    }

    public function test_bad_signatures_are_rejected_and_nothing_is_stored(): void
    {
        Bus::fake();

        $this->send($this->received(), 'mauvais_secret')->assertUnauthorized();
        $this->send($this->received(), null)->assertUnauthorized();

        $this->assertDatabaseCount('shop_bot_webhook_events', 0);
        Bus::assertNothingDispatched();
    }

    public function test_signature_covers_the_raw_body(): void
    {
        $raw = '{"event":"message.received"}';
        $signature = 'sha256='.hash_hmac('sha256', $raw, self::SECRET);

        $this->call('POST', route('webhooks.botisimo', $this->bot), [], [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_BOTISIMO_SIGNATURE' => $signature],
            '{"event":"message.received","forged":true}',
        )->assertUnauthorized();
    }

    public function test_a_secret_from_another_bot_is_rejected(): void
    {
        $other = ShopBot::create([
            'shop_id' => Shop::factory()->create(['user_id' => $this->vendor->id])->id,
            'botisimo_bot_id' => 'bot-2',
            'webhook_secret' => 'autre_secret',
        ]);

        $this->send($this->received(), 'autre_secret', $this->bot)->assertUnauthorized();
        $this->send($this->received(), 'autre_secret', $other)->assertOk();
    }

    public function test_bot_without_secret_rejects_everything(): void
    {
        $bot = ShopBot::create(['shop_id' => Shop::factory()->create(['user_id' => $this->vendor->id])->id]);

        $this->send($this->received(), '', $bot)->assertUnauthorized();
    }

    public function test_invalid_json_is_refused_even_when_signed(): void
    {
        $raw = 'pas du json';

        $this->call('POST', route('webhooks.botisimo', $this->bot), [], [], [],
            ['HTTP_X_BOTISIMO_SIGNATURE' => 'sha256='.hash_hmac('sha256', $raw, self::SECRET)], $raw,
        )->assertStatus(400);
    }

    public function test_replayed_event_is_processed_once(): void
    {
        Bus::fake();

        $this->send($this->received())->assertOk();
        $this->send($this->received())->assertOk();

        $this->assertDatabaseCount('shop_bot_webhook_events', 1);
        Bus::assertDispatchedTimes(ProcessBotisimoWebhook::class, 1);
    }

    public function test_successive_statuses_of_a_message_are_distinct_events(): void
    {
        Bus::fake();
        $status = fn (string $value) => ['event' => 'message.status', 'meta_message_id' => 'wamid.1', 'status' => $value];

        $this->send($status('delivered'))->assertOk();
        $this->send($status('read'))->assertOk();
        $this->send($status('read'))->assertOk();

        $this->assertDatabaseCount('shop_bot_webhook_events', 2);
    }

    public function test_events_without_id_are_deduplicated_on_their_exact_body(): void
    {
        Bus::fake();
        $payload = ['event' => 'message.received', 'from' => '2376', 'content' => 'x'];

        $this->send($payload)->assertOk();
        $this->send($payload)->assertOk();
        $this->send($payload + ['extra' => 1])->assertOk();

        $this->assertDatabaseCount('shop_bot_webhook_events', 2);
    }

    public function test_received_message_creates_the_conversation(): void
    {
        $this->send($this->received())->assertOk();

        $conversation = ShopBotConversation::firstOrFail();
        $this->assertSame('c1', $conversation->botisimo_conversation_id);
        $this->assertSame('237699000000', $conversation->client_phone);
        $this->assertSame('Awa', $conversation->client_name);
        $this->assertSame(1, $conversation->unread_count);
        $this->assertSame('Bonjour, le sac est disponible ?', $conversation->last_message_preview);
        $this->assertTrue($conversation->canSendFreeMessage());

        $message = ShopBotMessage::firstOrFail();
        $this->assertSame(ShopBotMessage::DIRECTION_INBOUND, $message->direction);
        $this->assertSame('wamid.1', $message->meta_message_id);
        $this->assertNotNull(ShopBotWebhookEvent::firstOrFail()->processed_at);
    }

    public static function envelopes(): array
    {
        $message = ['id' => 'm1', 'from' => '2376', 'content' => 'Salut'];

        return [
            'data' => [['event' => 'message.received', 'data' => $message]],
            'message' => [['event' => 'message.received', 'message' => $message]],
            'data.message' => [['event' => 'message.received', 'data' => ['message' => $message]]],
            'type au lieu de event' => [['type' => 'message.received'] + $message],
        ];
    }

    #[DataProvider('envelopes')]
    public function test_common_envelopes_are_understood(array $payload): void
    {
        $this->send($payload)->assertOk();

        $this->assertSame('Salut', ShopBotMessage::firstOrFail()->body);
    }

    public function test_conversation_with_only_a_phone_gets_the_real_id_later(): void
    {
        $this->send(['event' => 'message.received', 'message_id' => 'm1', 'from' => '2376', 'content' => 'a'])->assertOk();
        $this->assertSame('phone:2376', ShopBotConversation::firstOrFail()->botisimo_conversation_id);

        $this->send(['event' => 'message.received', 'message_id' => 'm2', 'from' => '2376', 'content' => 'b', 'conversation_id' => 'c9'])->assertOk();

        $this->assertDatabaseCount('shop_bot_conversations', 1);
        $this->assertSame('c9', ShopBotConversation::firstOrFail()->botisimo_conversation_id);
        $this->assertSame(2, ShopBotConversation::firstOrFail()->unread_count);
    }

    public function test_a_message_id_is_never_taken_for_a_conversation_id(): void
    {
        $this->send(['event' => 'message.received', 'id' => 'm1', 'from' => '2376', 'content' => 'a', 'status' => 'received'])->assertOk();
        $this->send(['event' => 'message.received', 'id' => 'm2', 'from' => '2376', 'content' => 'b', 'status' => 'received'])->assertOk();

        $conversation = ShopBotConversation::firstOrFail();
        $this->assertDatabaseCount('shop_bot_conversations', 1);
        $this->assertSame('phone:2376', $conversation->botisimo_conversation_id);
        $this->assertSame('active', $conversation->status);
    }

    public function test_status_updates_a_message_and_never_goes_backwards(): void
    {
        $this->send($this->received())->assertOk();
        $message = ShopBotMessage::firstOrFail();
        $status = fn (string $value) => ['event' => 'message.status', 'meta_message_id' => 'wamid.1', 'status' => $value];

        $this->send($status('read'))->assertOk();
        $this->assertSame('read', $message->fresh()->status);

        $this->send($status('delivered'))->assertOk();
        $this->assertSame('read', $message->fresh()->status);
    }

    public function test_status_cannot_touch_another_bots_message(): void
    {
        $this->send($this->received())->assertOk();
        $other = ShopBot::create([
            'shop_id' => Shop::factory()->create(['user_id' => $this->vendor->id])->id,
            'botisimo_bot_id' => 'bot-2',
            'webhook_secret' => 'autre',
        ]);

        $this->send(['event' => 'message.status', 'meta_message_id' => 'wamid.1', 'status' => 'read'], 'autre', $other)->assertOk();

        $this->assertNull(ShopBotMessage::firstOrFail()->status);
    }

    public function test_transfer_keyword_alerts_the_vendor_once_ignoring_accents_and_case(): void
    {
        Notification::fake();

        $this->send($this->received(['content' => 'Je veux faire une RECLAMATION']))->assertOk();
        $this->send($this->received(['message_id' => 'm2', 'meta_message_id' => 'wamid.2', 'content' => 'Remboursement svp']))->assertOk();

        Notification::assertSentToTimes($this->vendor, AssistantHumanTransferNotification::class, 1);
    }

    public function test_no_alert_without_keyword(): void
    {
        Notification::fake();

        $this->send($this->received())->assertOk();

        Notification::assertNothingSent();
    }

    public function test_unknown_event_is_acknowledged(): void
    {
        $this->send(['event' => 'autre.evenement', 'id' => 'x'])->assertOk();

        $this->assertNotNull(ShopBotWebhookEvent::firstOrFail()->processed_at);
    }

    public function test_old_events_are_pruned(): void
    {
        Bus::fake();
        $this->send($this->received())->assertOk();
        ShopBotWebhookEvent::query()->update(['created_at' => now()->subDays(31)]);
        $this->send($this->received(['message_id' => 'm2', 'meta_message_id' => 'wamid.2']))->assertOk();

        $this->artisan('model:prune', ['--model' => [ShopBotWebhookEvent::class]])->assertSuccessful();

        $this->assertDatabaseCount('shop_bot_webhook_events', 1);
    }
}
