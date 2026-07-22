<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);
    }

    private function fakeSocialiteUser(string $id, string $email, string $name): void
    {
        $googleUser = new SocialiteUser();
        $googleUser->id = $id;
        $googleUser->email = $email;
        $googleUser->name = $name;

        Socialite::shouldReceive('driver->user')->andReturn($googleUser);
    }

    public function test_redirect_stores_account_type_in_session(): void
    {
        Socialite::shouldReceive('driver->redirect')->andReturn(redirect('https://accounts.google.com/fake'));

        $this->get(route('auth.google.redirect', ['account_type' => 'vendeur']));

        $this->assertSame('vendeur', session('auth_google_account_type'));
    }

    public function test_new_account_created_as_vendeur(): void
    {
        $this->fakeSocialiteUser('g-1', 'vendeur@example.com', 'Vendeur Test');

        $response = $this->withSession(['auth_google_account_type' => 'vendeur'])
            ->get(route('auth.google.callback'));

        $user = User::where('email', 'vendeur@example.com')->firstOrFail();

        $this->assertSame(User::ROLE_SUPPLIER, $user->role);
        $this->assertTrue($user->is_vendeur);
        $this->assertFalse($user->is_prestataire);
        $response->assertRedirect(route('backoffice.supplier.dashboard'));
    }

    public function test_new_account_created_as_prestataire(): void
    {
        $this->fakeSocialiteUser('g-2', 'prestataire@example.com', 'Prestataire Test');

        $response = $this->withSession(['auth_google_account_type' => 'prestataire'])
            ->get(route('auth.google.callback'));

        $user = User::where('email', 'prestataire@example.com')->firstOrFail();

        $this->assertSame(User::ROLE_SUPPLIER, $user->role);
        $this->assertFalse($user->is_vendeur);
        $this->assertTrue($user->is_prestataire);
        $response->assertRedirect(route('backoffice.supplier.dashboard'));
    }

    public function test_new_account_created_as_both(): void
    {
        $this->fakeSocialiteUser('g-3', 'both@example.com', 'Both Test');

        $response = $this->withSession(['auth_google_account_type' => 'both'])
            ->get(route('auth.google.callback'));

        $user = User::where('email', 'both@example.com')->firstOrFail();

        $this->assertSame(User::ROLE_SUPPLIER, $user->role);
        $this->assertTrue($user->is_vendeur);
        $this->assertTrue($user->is_prestataire);
        $response->assertRedirect(route('backoffice.supplier.dashboard'));
    }

    public function test_new_account_created_as_client(): void
    {
        $this->fakeSocialiteUser('g-4', 'client@example.com', 'Client Test');

        $response = $this->withSession(['auth_google_account_type' => 'client'])
            ->get(route('auth.google.callback'));

        $user = User::where('email', 'client@example.com')->firstOrFail();

        $this->assertSame(User::ROLE_USER, $user->role);
        $this->assertFalse($user->is_vendeur);
        $this->assertFalse($user->is_prestataire);
        $response->assertRedirect(route('user.dashboard'));
    }

    public function test_new_account_without_account_type_is_mixed(): void
    {
        $this->fakeSocialiteUser('g-5', 'generic@example.com', 'Generic Test');

        $response = $this->get(route('auth.google.callback'));

        $user = User::where('email', 'generic@example.com')->firstOrFail();

        $this->assertSame(User::ROLE_SUPPLIER, $user->role);
        $this->assertTrue($user->is_vendeur);
        $this->assertTrue($user->is_prestataire);
        $response->assertRedirect(route('backoffice.supplier.dashboard'));
    }

    public function test_existing_user_role_is_never_overwritten_on_google_login(): void
    {
        $existing = User::factory()->create([
            'email' => 'existing-client@example.com',
            'role' => User::ROLE_USER,
            'is_vendeur' => false,
            'is_prestataire' => false,
            'google_id' => null,
        ]);

        $this->fakeSocialiteUser('g-6', 'existing-client@example.com', 'Existing Client');

        // Simulate arriving via the generic Google button (no account_type stashed).
        $response = $this->get(route('auth.google.callback'));

        $existing->refresh();

        $this->assertSame(User::ROLE_USER, $existing->role);
        $this->assertFalse($existing->is_vendeur);
        $this->assertFalse($existing->is_prestataire);
        $this->assertSame('g-6', $existing->google_id);
        $response->assertRedirect(route('user.dashboard'));
    }
}
