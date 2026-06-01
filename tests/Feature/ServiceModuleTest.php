<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Favorite;
use App\Models\QuoteRequest;
use App\Models\Service;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceModuleTest extends TestCase
{
    use RefreshDatabase;

    private function makeSupplierWithShop(): array
    {
        $supplier = User::factory()->create(['role' => User::ROLE_SUPPLIER]);
        $shop = Shop::create([
            'name' => 'Studio Test',
            'description' => 'desc',
            'city' => 'Douala',
            'district' => 'Akwa',
            'phone' => '690000000',
            'user_id' => $supplier->id,
        ]);

        return [$supplier, $shop];
    }

    public function test_services_index_is_public_and_lists_active_services(): void
    {
        [$supplier, $shop] = $this->makeSupplierWithShop();
        Service::create([
            'code' => 'SVC-1', 'title' => 'Logo design', 'price' => '25000',
            'quote_only' => false, 'is_active' => true, 'city' => 'Douala',
            'user_id' => $supplier->id, 'shop_id' => $shop->id,
        ]);

        $response = $this->get('/services');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Services/Index')
            ->has('services.data', 1)
        );
    }

    public function test_quote_only_service_show_renders(): void
    {
        [$supplier, $shop] = $this->makeSupplierWithShop();
        $service = Service::create([
            'code' => 'SVC-2', 'title' => 'Site web', 'price' => null,
            'quote_only' => true, 'is_active' => true,
            'user_id' => $supplier->id, 'shop_id' => $shop->id,
        ]);

        $response = $this->get("/services/{$service->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Services/Show')
            ->where('service.quote_only', true)
            ->where('service.current_price', null)
        );
    }

    public function test_guest_can_submit_quote_request_and_it_is_stored(): void
    {
        [$supplier, $shop] = $this->makeSupplierWithShop();
        $service = Service::create([
            'code' => 'SVC-3', 'title' => 'Devis service', 'price' => null,
            'quote_only' => true, 'is_active' => true,
            'user_id' => $supplier->id, 'shop_id' => $shop->id,
        ]);

        $response = $this->post("/services/{$service->id}/quote", [
            'customer_name' => 'Jean',
            'customer_phone' => '691112233',
            'budget' => '100000',
            'message' => 'Besoin urgent',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('quote_requests', [
            'service_id' => $service->id,
            'customer_name' => 'Jean',
            'status' => QuoteRequest::STATUS_PENDING,
        ]);
    }

    public function test_supplier_can_create_service(): void
    {
        [$supplier, $shop] = $this->makeSupplierWithShop();

        $response = $this->actingAs($supplier)->post('/backoffice/supplier/services', [
            'shop_id' => $shop->id,
            'title' => 'Photographie',
            'description' => 'Couverture',
            'quote_only' => false,
            'price' => 40000,
            'is_active' => true,
            'city' => 'Douala',
        ]);

        $response->assertRedirect(route('backoffice.supplier.services.index'));
        $this->assertDatabaseHas('services', [
            'title' => 'Photographie',
            'user_id' => $supplier->id,
            'shop_id' => $shop->id,
        ]);
    }

    public function test_supplier_cannot_edit_other_suppliers_service(): void
    {
        [$supplierA, $shopA] = $this->makeSupplierWithShop();
        [$supplierB] = $this->makeSupplierWithShop();
        $service = Service::create([
            'code' => 'SVC-A', 'title' => 'A', 'price' => '1000',
            'quote_only' => false, 'is_active' => true,
            'user_id' => $supplierA->id, 'shop_id' => $shopA->id,
        ]);

        $response = $this->actingAs($supplierB)->put("/backoffice/supplier/services/{$service->id}", [
            'title' => 'Hacked', 'quote_only' => false, 'price' => 1, 'is_active' => true,
        ]);

        $response->assertStatus(403);
    }

    public function test_authenticated_user_can_favorite_a_service(): void
    {
        [$supplier, $shop] = $this->makeSupplierWithShop();
        $service = Service::create([
            'code' => 'SVC-F', 'title' => 'Fav', 'price' => '1000',
            'quote_only' => false, 'is_active' => true,
            'user_id' => $supplier->id, 'shop_id' => $shop->id,
        ]);
        $client = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($client)->post('/favorites/toggle', [
            'type' => 'service', 'id' => $service->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $client->id,
            'favoritable_type' => Service::class,
            'favoritable_id' => $service->id,
        ]);
    }

    public function test_guest_cannot_toggle_favorite(): void
    {
        $response = $this->post('/favorites/toggle', ['type' => 'product', 'id' => 1]);
        $response->assertRedirect('/login');
    }
}
