<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTypeTest extends TestCase
{
    use RefreshDatabase;

    private function proPayload(string $accountType, string $email, string $username): array
    {
        return [
            'account_type' => $accountType,
            'name' => 'Test Pro',
            'username' => $username,
            'email' => $email,
            'phone_country_code' => '237',
            'phone_number' => '690112233',
            'address' => 'Douala',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];
    }

    public function test_client_registration_sets_user_role_no_capabilities(): void
    {
        $this->post('/register', [
            'account_type' => 'client',
            'name' => 'Client',
            'email' => 'client@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect();

        $user = User::where('email', 'client@test.com')->first();
        $this->assertSame(User::ROLE_USER, $user->role);
        $this->assertFalse($user->is_vendeur);
        $this->assertFalse($user->is_prestataire);
    }

    public function test_vendeur_registration_sets_supplier_with_vendeur_capability(): void
    {
        $this->post('/register', $this->proPayload('vendeur', 'v@test.com', 'vendeur1'))
            ->assertRedirect();

        $user = User::where('email', 'v@test.com')->first();
        $this->assertSame(User::ROLE_SUPPLIER, $user->role);
        $this->assertTrue($user->isVendeur());
        $this->assertFalse($user->isPrestataire());
    }

    public function test_prestataire_registration_sets_supplier_with_prestataire_capability(): void
    {
        $this->post('/register', $this->proPayload('prestataire', 'p@test.com', 'presta1'))
            ->assertRedirect();

        $user = User::where('email', 'p@test.com')->first();
        $this->assertSame(User::ROLE_SUPPLIER, $user->role);
        $this->assertFalse($user->isVendeur());
        $this->assertTrue($user->isPrestataire());
    }

    public function test_both_registration_sets_both_capabilities(): void
    {
        $this->post('/register', $this->proPayload('both', 'b@test.com', 'both1'))
            ->assertRedirect();

        $user = User::where('email', 'b@test.com')->first();
        $this->assertSame(User::ROLE_SUPPLIER, $user->role);
        $this->assertTrue($user->isVendeur());
        $this->assertTrue($user->isPrestataire());
    }

    public function test_capabilities_only_apply_to_pro_accounts(): void
    {
        $client = User::factory()->create(['role' => User::ROLE_USER, 'is_vendeur' => true]);
        // Même avec is_vendeur=true en base, un compte non-pro n'est pas vendeur
        $this->assertFalse($client->isVendeur());
    }
}
