<?php

namespace Tests\Feature\Botisimo;

use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopBot;
use App\Models\ShopBotConversation;
use App\Models\ShopBotCreditUsage;
use App\Models\ShopBotProduct;
use App\Models\User;
use App\Services\Botisimo\BotisimoClient;
use App\Services\Channels\ChannelProvider;
use App\Services\Channels\WhatsAppChannel;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShopBotModelTest extends TestCase
{
    use RefreshDatabase;

    private function shop(): Shop
    {
        return Shop::factory()->create(['user_id' => User::factory()->create()->id]);
    }

    public function test_webhook_secret_is_encrypted_at_rest_and_hidden_from_json(): void
    {
        $bot = ShopBot::create(['shop_id' => $this->shop()->id, 'webhook_secret' => 'whsec_123']);

        $raw = DB::table('shop_bots')->where('id', $bot->id)->value('webhook_secret');

        $this->assertNotSame('whsec_123', $raw);
        $this->assertStringNotContainsString('whsec_123', $raw);
        $this->assertSame('whsec_123', $bot->fresh()->webhook_secret);
        $this->assertArrayNotHasKey('webhook_secret', $bot->toArray());
    }

    public function test_a_shop_has_only_one_bot_per_channel(): void
    {
        $shop = $this->shop();
        ShopBot::create(['shop_id' => $shop->id]);

        $this->assertSame(ShopBot::STATUS_PENDING, $shop->bot->fresh()->status);

        $this->expectException(QueryException::class);
        ShopBot::create(['shop_id' => $shop->id]);
    }

    public function test_free_message_window_is_twenty_four_hours(): void
    {
        $conversation = new ShopBotConversation;

        $this->assertFalse($conversation->canSendFreeMessage());

        $conversation->last_inbound_at = now()->subHours(23);
        $this->assertTrue($conversation->canSendFreeMessage());

        $conversation->last_inbound_at = now()->subHours(25);
        $this->assertFalse($conversation->canSendFreeMessage());
    }

    public function test_catalog_link_survives_product_deletion_for_the_remote_delete(): void
    {
        $shop = $this->shop();
        $bot = ShopBot::create(['shop_id' => $shop->id]);
        $product = Product::factory()->create(['shop_id' => $shop->id, 'user_id' => $shop->user_id]);
        $item = ShopBotProduct::create([
            'shop_bot_id' => $bot->id,
            'catalogable_type' => 'product',
            'catalogable_id' => $product->id,
            'botisimo_product_id' => 'remote-1',
        ]);

        $this->assertTrue($item->catalogable->is($product));

        $product->delete();

        $this->assertDatabaseHas('shop_bot_products', ['botisimo_product_id' => 'remote-1']);
    }

    public function test_credit_ledger_survives_shop_deletion(): void
    {
        $shop = $this->shop();
        $usage = ShopBotCreditUsage::create(['shop_id' => $shop->id, 'action' => 'message', 'credits' => 10]);

        $shop->delete();

        $this->assertNull($usage->fresh()->shop_id);
    }

    public function test_whatsapp_channel_reads_redirect_url_and_strips_the_plus_sign(): void
    {
        config(['botisimo.api_key' => 'bts_x', 'botisimo.base_url' => 'https://botisimo.test', 'botisimo.rate_limit.max_requests' => 100]);
        Http::fake(['*/connect' => Http::response(['redirect_url' => 'https://meta.test/signup']), '*' => Http::response([])]);

        $channel = app(ChannelProvider::class);
        $bot = new ShopBot(['botisimo_bot_id' => 'b1']);

        $this->assertInstanceOf(WhatsAppChannel::class, $channel);
        $this->assertSame('https://meta.test/signup', $channel->connectionUrl($bot, 'https://x/cb'));

        $channel->sendText($bot, '+237699000000', 'Bonjour');

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/v1/messages/b1/send')
            && $r['to'] === '237699000000' && $r['type'] === 'text');
        $this->assertTrue(app()->bound(BotisimoClient::class));
    }
}
