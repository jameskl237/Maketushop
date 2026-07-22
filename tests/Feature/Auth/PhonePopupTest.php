<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhonePopupTest extends TestCase
{
    use RefreshDatabase;

    public function test_must_set_phone_true_for_google_user_without_phone(): void
    {
        $user = User::factory()->create(['google_id' => 'g-1', 'phone' => null]);

        $response = $this->actingAs($user)->get(route('user.dashboard'));

        $response->assertInertia(fn ($page) => $page->where('auth.must_set_phone', true));
    }

    public function test_must_set_phone_false_after_phone_saved(): void
    {
        $user = User::factory()->create(['google_id' => 'g-2', 'phone' => null]);

        $this->actingAs($user)->patch(route('profile.phone.update'), ['phone' => '237600000000']);

        $response = $this->actingAs($user->fresh())->get(route('user.dashboard'));

        $response->assertInertia(fn ($page) => $page->where('auth.must_set_phone', false));
    }

    public function test_must_set_phone_false_for_non_google_user(): void
    {
        $user = User::factory()->create(['google_id' => null, 'phone' => null]);

        $response = $this->actingAs($user)->get(route('user.dashboard'));

        $response->assertInertia(fn ($page) => $page->where('auth.must_set_phone', false));
    }
}
