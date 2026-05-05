<?php

namespace Tests\Feature\Backoffice;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_access_dashboard()
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_SUPERADMIN,
        ]);

        // Create some orders to test the logic
        Order::factory()->count(3)->create([
            'user_id' => $superAdmin->id,
            'status' => Order::STATUS_DELIVERED,
            'total_price' => 1000,
        ]);

        $response = $this->actingAs($superAdmin)->get('/backoffice/superadmin/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Backoffice/SuperAdmin/Dashboard')
            ->has('stats.total_revenue')
            ->has('stats.total_payouts')
        );
    }

    public function test_non_superadmin_cannot_access_dashboard()
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
        ]);

        $response = $this->actingAs($user)->get('/backoffice/superadmin/dashboard');

        $response->assertStatus(403);
    }
}
