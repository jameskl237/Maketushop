<?php

namespace Tests\Feature\Botisimo;

use App\Jobs\Botisimo\ReconcileShopCatalog;
use App\Jobs\Botisimo\SyncProductToBotisimo;
use App\Models\Category;
use App\Models\Product;
use App\Models\Service;
use App\Models\Shop;
use App\Models\ShopBot;
use App\Models\ShopBotProduct;
use App\Models\User;
use App\Services\Botisimo\CatalogMapper;
use App\Services\Botisimo\CatalogSynchronizer;
use App\Support\MediaUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CatalogSyncTest extends TestCase
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
            'app.url' => 'https://maketu.test',
            'botisimo.api_key' => 'bts_x',
            'botisimo.base_url' => 'https://botisimo.test',
            'botisimo.rate_limit.max_requests' => 1000,
            'botisimo.rate_limit.background_per_minute' => 1000,
            'botisimo.retry.max_attempts' => 1,
        ]);

        $this->vendor = User::factory()->create(['role' => 'supplier', 'is_vendeur' => true]);
        $this->shop = Shop::factory()->create(['user_id' => $this->vendor->id]);
        $this->shop->subscriptions()->create(['plan' => 'standard', 'status' => 'active', 'ends_at' => now()->addDays(10)]);
        $this->bot = ShopBot::create(['shop_id' => $this->shop->id, 'botisimo_bot_id' => 'bot-1', 'webhook_secret' => 's']);
    }

    private function product(array $attributes = []): Product
    {
        return Product::factory()->create($attributes + [
            'shop_id' => $this->shop->id,
            'user_id' => $this->vendor->id,
            'in_stock' => true,
            'quantity' => 5,
            'price' => '1500',
        ]);
    }

    private function fakeCatalog(): void
    {
        Http::fake([
            'botisimo.test/v1/catalog' => Http::response(['id' => 'remote-1'], 201),
            'botisimo.test/v1/catalog/*' => Http::response([]),
        ]);
    }

    private function catalogCalls(string $method): int
    {
        return collect(Http::recorded())->filter(fn ($pair) => $pair[0]->method() === $method)->count();
    }

    public function test_mapping_uses_the_price_customers_pay_and_public_urls(): void
    {
        $category = Category::create(['name' => 'Mode', 'slug' => 'mode']);
        $product = $this->product([
            'name' => 'Pagne',
            'price' => '1500',
            'promotion_price' => '2000', // ancien prix barré
            'long_description' => '<p>Tissu &amp; <b>wax</b></p>',
            'category_id' => $category->id,
        ]);
        $product->medias()->create(['url' => 'products/v.mp4', 'type' => 'video', 'is_principal' => true]);
        $product->medias()->create(['url' => 'products/a.jpg', 'type' => 'image']);

        $payload = app(CatalogMapper::class)->map($product->fresh(), true);

        $this->assertSame('Pagne', $payload['name']);
        $this->assertSame(1500.0, $payload['price']);
        $this->assertSame('XAF', $payload['currency']);
        $this->assertSame('Tissu & wax', $payload['description']);
        $this->assertSame('Mode', $payload['category']);
        $this->assertSame('product', $payload['type']);
        $this->assertTrue($payload['is_ad']);
        $this->assertSame('https://maketu.test/storage/products/a.jpg', $payload['image_url']);
        $this->assertSame("https://maketu.test/products/{$product->id}", $payload['payment_link']);
    }

    public function test_product_with_no_quantity_is_out_of_stock_like_on_the_site(): void
    {
        $this->assertFalse(app(CatalogMapper::class)->map($this->product(['quantity' => 0]), false)['in_stock']);
        $this->assertFalse(app(CatalogMapper::class)->map($this->product(['in_stock' => false]), false)['in_stock']);
    }

    public function test_quote_only_service_is_sent_without_price(): void
    {
        $service = Service::create([
            'code' => 'S1', 'title' => 'Couture', 'quote_only' => true, 'is_active' => false,
            'shop_id' => $this->shop->id, 'user_id' => $this->vendor->id,
        ]);

        $payload = app(CatalogMapper::class)->map($service, false);

        $this->assertSame('Couture', $payload['name']);
        $this->assertSame('service', $payload['type']);
        $this->assertFalse($payload['active']);
        $this->assertArrayNotHasKey('price', $payload);
    }

    public function test_absolute_url_helper(): void
    {
        $this->assertSame('https://maketu.test/storage/a.jpg', MediaUrl::absolute('a.jpg'));
        $this->assertSame('https://cdn.test/a.jpg', MediaUrl::absolute('https://cdn.test/a.jpg'));
        $this->assertSame('https://cdn.test/a.jpg', MediaUrl::absolute('//cdn.test/a.jpg'));
        $this->assertNull(MediaUrl::absolute('data:image/png;base64,xx'));
        $this->assertNull(MediaUrl::absolute(null));
    }

    public function test_creating_a_product_pushes_it_to_the_catalog(): void
    {
        $this->fakeCatalog();

        $product = $this->product(['name' => 'Sac']);

        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r['bot_id'] === 'bot-1' && $r['name'] === 'Sac');

        $item = ShopBotProduct::firstWhere('catalogable_id', $product->id);
        $this->assertSame('remote-1', $item->botisimo_product_id);
        $this->assertNotNull($item->sync_hash);
        $this->assertNotNull($item->synced_at);
    }

    public function test_unchanged_product_is_not_sent_again(): void
    {
        $this->fakeCatalog();
        $product = $this->product();

        $product->touch();
        $product->save();

        $this->assertSame(1, $this->catalogCalls('POST'));
        $this->assertSame(0, $this->catalogCalls('PUT'));
    }

    public function test_changing_a_product_updates_the_remote_entry(): void
    {
        $this->fakeCatalog();
        $product = $this->product();

        $product->update(['in_stock' => false]);

        Http::assertSent(fn (Request $r) => $r->method() === 'PUT'
            && str_ends_with($r->url(), '/v1/catalog/remote-1') && $r['in_stock'] === false && ! isset($r['bot_id']));
    }

    public function test_adding_a_photo_updates_the_remote_entry(): void
    {
        $this->fakeCatalog();
        $product = $this->product();

        $product->medias()->create(['url' => 'products/a.jpg', 'type' => 'image', 'is_principal' => true]);

        Http::assertSent(fn (Request $r) => $r->method() === 'PUT'
            && $r['image_url'] === 'https://maketu.test/storage/products/a.jpg');
    }

    public function test_deleting_a_product_deletes_the_remote_entry(): void
    {
        $this->fakeCatalog();
        $product = $this->product();

        $product->delete();

        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/v1/catalog/remote-1'));
        $this->assertDatabaseCount('shop_bot_products', 0);
    }

    public function test_moving_a_product_removes_it_from_the_old_shop_catalog(): void
    {
        $this->fakeCatalog();
        $product = $this->product();
        $otherShop = Shop::factory()->create(['user_id' => $this->vendor->id]);

        $product->update(['shop_id' => $otherShop->id]);

        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE');
        $this->assertDatabaseCount('shop_bot_products', 0);
    }

    public function test_remote_deletion_is_repaired_by_recreating_the_entry(): void
    {
        Http::fake([
            'botisimo.test/v1/catalog' => Http::response(['id' => 'remote-2'], 201),
            'botisimo.test/v1/catalog/gone' => Http::response([], 404),
        ]);
        Bus::fake();
        $product = $this->product();
        $item = ShopBotProduct::create([
            'shop_bot_id' => $this->bot->id, 'catalogable_type' => 'product',
            'catalogable_id' => $product->id, 'botisimo_product_id' => 'gone', 'sync_hash' => 'old',
        ]);

        app(CatalogSynchronizer::class)->sync($this->bot, 'product', $product->id);

        $this->assertSame('remote-2', $item->fresh()->botisimo_product_id);
    }

    public function test_api_error_is_recorded_and_the_next_run_retries(): void
    {
        Http::fakeSequence('botisimo.test/v1/catalog')->push(['error' => 'x'], 400)->push(['id' => 'remote-1'], 201);
        $product = $this->product();

        $item = ShopBotProduct::firstWhere('catalogable_id', $product->id);
        $this->assertNotNull($item->sync_error);
        $this->assertNull($item->botisimo_product_id);

        app(CatalogSynchronizer::class)->sync($this->bot, 'product', $product->id);

        $this->assertNull($item->fresh()->sync_error);
        $this->assertSame('remote-1', $item->fresh()->botisimo_product_id);
    }

    public function test_shops_without_a_bot_trigger_nothing(): void
    {
        Bus::fake();
        $other = Shop::factory()->create(['user_id' => $this->vendor->id]);

        Product::factory()->create(['shop_id' => $other->id, 'user_id' => $this->vendor->id]);
        Service::create(['code' => 'S', 'title' => 'x', 'user_id' => $this->vendor->id]);

        Bus::assertNotDispatched(SyncProductToBotisimo::class);
    }

    public function test_job_releases_itself_on_rate_limit(): void
    {
        config(['botisimo.rate_limit.max_requests' => 0]);
        Http::fake();
        Bus::fake();
        $product = $this->product();

        $job = (new SyncProductToBotisimo($this->bot->id, 'product', $product->id))->withFakeQueueInteractions();
        $job->handle(app(CatalogSynchronizer::class));

        $job->assertReleased();
        Http::assertNothingSent();
    }

    public function test_job_exposes_the_expected_queue_guards(): void
    {
        $job = new SyncProductToBotisimo(1, 'product', 2);

        $this->assertSame('1:product:2', $job->uniqueId());
        $this->assertTrue($job->afterCommit);
        $this->assertGreaterThan(now()->addHour(), $job->retryUntil());
        $this->assertCount(2, $job->middleware());
    }

    public function test_reconcile_recreates_missing_entries(): void
    {
        Bus::fake();
        $product = $this->product();
        $other = $this->product();
        foreach ([[$product, 'kept'], [$other, 'lost']] as [$model, $remoteId]) {
            ShopBotProduct::create([
                'shop_bot_id' => $this->bot->id, 'catalogable_type' => 'product',
                'catalogable_id' => $model->id, 'botisimo_product_id' => $remoteId, 'sync_hash' => 'h',
            ]);
        }
        Http::fake(['botisimo.test/v1/catalog*' => Http::response(['data' => [['id' => 'kept'], ['id' => 'stray']]])]);

        $result = app(CatalogSynchronizer::class)->reconcile($this->bot);

        $this->assertSame(['missing' => 1, 'orphans' => 1], $result);
        $this->assertDatabaseHas('shop_bot_products', ['botisimo_product_id' => 'kept']);
        $this->assertDatabaseMissing('shop_bot_products', ['botisimo_product_id' => 'lost']);
        Http::assertNotSent(fn (Request $r) => $r->method() === 'DELETE');
    }

    public function test_prune_deletes_unknown_remote_entries(): void
    {
        Bus::fake();
        $product = $this->product();
        ShopBotProduct::create([
            'shop_bot_id' => $this->bot->id, 'catalogable_type' => 'product',
            'catalogable_id' => $product->id, 'botisimo_product_id' => 'kept',
        ]);
        Http::fake([
            'botisimo.test/v1/catalog?*' => Http::response([['id' => 'kept'], ['id' => 'stray']]),
            'botisimo.test/v1/catalog/stray' => Http::response([]),
        ]);

        app(CatalogSynchronizer::class)->reconcile($this->bot, prune: true);

        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/stray'));
    }

    public function test_reconcile_paginates_by_two_hundred(): void
    {
        $page = fn (int $from, int $count) => collect(range($from, $from + $count - 1))->map(fn ($i) => ['id' => "p{$i}"])->all();
        Http::fakeSequence()->push($page(1, 200))->push($page(201, 3));

        app(CatalogSynchronizer::class)->reconcile($this->bot);

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'limit=200&offset=200'));
    }

    public function test_reconcile_stops_if_the_api_ignores_the_offset(): void
    {
        $sameRows = collect(range(1, 200))->map(fn ($i) => ['id' => "p{$i}"])->all();
        Http::fake(['*' => Http::response($sameRows)]);

        app(CatalogSynchronizer::class)->reconcile($this->bot);

        Http::assertSentCount(2);
    }

    public static function unusableResponses(): array
    {
        return [
            'format inconnu' => [['result' => 'ok']],
            'lignes sans identifiant' => [['data' => [['name' => 'x']]]],
            'aucun identifiant commun' => [['data' => [['id' => 'autre']]]],
        ];
    }

    #[DataProvider('unusableResponses')]
    public function test_reconcile_touches_nothing_on_an_unusable_response(array $response): void
    {
        Bus::fake();
        $product = $this->product();
        ShopBotProduct::create([
            'shop_bot_id' => $this->bot->id, 'catalogable_type' => 'product',
            'catalogable_id' => $product->id, 'botisimo_product_id' => 'mine', 'sync_hash' => 'h',
        ]);
        Http::fake(['*' => Http::response($response)]);

        $this->assertNull(app(CatalogSynchronizer::class)->reconcile($this->bot, prune: true));

        $this->assertDatabaseHas('shop_bot_products', ['botisimo_product_id' => 'mine', 'sync_hash' => 'h']);
        Http::assertNotSent(fn (Request $r) => $r->method() === 'DELETE');
    }

    public function test_full_sync_catches_sql_cascade_deletions_and_skips_unchanged_items(): void
    {
        $this->fakeCatalog();
        $unchanged = $this->product();
        $deleted = $this->product();
        $deletedItem = ShopBotProduct::firstWhere('catalogable_id', $deleted->id);
        $deletedItem->update(['botisimo_product_id' => 'remote-deleted']);
        DB::table('products')->where('id', $deleted->id)->delete(); // aucun observer

        Bus::fake();
        $queued = app(CatalogSynchronizer::class)->queueChanges($this->bot);

        $this->assertSame(1, $queued);
        Bus::assertDispatched(SyncProductToBotisimo::class, fn ($job) => $job->catalogableId === $deleted->id);
        Bus::assertNotDispatched(SyncProductToBotisimo::class, fn ($job) => $job->catalogableId === $unchanged->id);
    }

    public function test_command_targets_one_shop_or_all(): void
    {
        Bus::fake();
        $other = Shop::factory()->create(['user_id' => $this->vendor->id]);
        ShopBot::create(['shop_id' => $other->id, 'botisimo_bot_id' => 'bot-2', 'webhook_secret' => 's']);
        ShopBot::create(['shop_id' => Shop::factory()->create(['user_id' => $this->vendor->id])->id]);

        $this->artisan('botisimo:sync-catalog')->assertSuccessful();
        Bus::assertDispatchedTimes(ReconcileShopCatalog::class, 2);

        $this->artisan('botisimo:sync-catalog', ['shop' => $other->id])->assertSuccessful();
        Bus::assertDispatchedTimes(ReconcileShopCatalog::class, 3);
    }

    public function test_vendor_sync_button_enables_catalog_ai_and_queues_the_sync(): void
    {
        Http::fake(['botisimo.test/v1/catalog/bot-1/ai' => Http::response([])]);
        Bus::fake();

        $this->actingAs($this->vendor)
            ->post(route('backoffice.supplier.assistant.catalog.sync', $this->shop))
            ->assertSessionHas('success');

        $this->assertTrue($this->bot->fresh()->catalog_ai_enabled);
        Http::assertSent(fn (Request $r) => $r->method() === 'PUT' && $r['enabled'] === true);
        Bus::assertDispatched(ReconcileShopCatalog::class);
    }

    public function test_highlight_toggle_resends_the_entry(): void
    {
        $this->fakeCatalog();
        $product = $this->product();
        $item = ShopBotProduct::firstWhere('catalogable_id', $product->id);

        $this->actingAs($this->vendor)
            ->patch(route('backoffice.supplier.assistant.catalog.update', [$this->shop, $item]), ['is_ad' => true])
            ->assertRedirect();

        $this->assertTrue($item->fresh()->is_ad);
        Http::assertSent(fn (Request $r) => $r->method() === 'PUT' && $r['is_ad'] === true);
    }

    public function test_vendor_cannot_touch_another_shops_catalog(): void
    {
        $this->fakeCatalog();
        $item = ShopBotProduct::firstWhere('catalogable_id', $this->product()->id);

        $intruder = User::factory()->create(['role' => 'supplier']);
        $intruderShop = Shop::factory()->create(['user_id' => $intruder->id]);
        $intruderShop->subscriptions()->create(['plan' => 'standard', 'status' => 'active', 'ends_at' => now()->addDay()]);
        ShopBot::create(['shop_id' => $intruderShop->id, 'botisimo_bot_id' => 'bot-9', 'webhook_secret' => 's']);
        $this->actingAs($intruder);

        $this->patch(route('backoffice.supplier.assistant.catalog.update', [$this->shop, $item]), ['is_ad' => true])->assertForbidden();
        $this->post(route('backoffice.supplier.assistant.catalog.sync', $this->shop))->assertForbidden();
        // Sa propre boutique dans l'URL ne donne pas accès à la ligne d'un autre.
        $this->patch(route('backoffice.supplier.assistant.catalog.update', [$intruderShop, $item]), ['is_ad' => true])->assertNotFound();

        $this->assertFalse($item->fresh()->is_ad);
    }

    public function test_page_exposes_catalog_progress(): void
    {
        $this->fakeCatalog();
        $this->product(['name' => 'Sac']);

        $this->actingAs($this->vendor)
            ->get(route('backoffice.supplier.assistant.show', $this->shop))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('catalogStats.total', 1)
                ->where('catalogStats.synced', 1)
                ->where('catalogStats.errors', 0)
                ->where('catalog.data.0.name', 'Sac')
                ->where('catalog.data.0.synced', true));
    }
}
