<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // --- 03 TEST USERS FOR CONNECTION ---
        
        // 1. Admin
        User::updateOrCreate(
            ['email' => 'admin@maketushop.com'],
            [
                'name' => 'Admin Maketu',
                'username' => 'admin_test',
                'role' => User::ROLE_ADMIN,
                'phone' => '237611111111',
                'address' => 'Douala, Cameroun',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
            ]
        );

        // 2. Supplier
        User::updateOrCreate(
            ['email' => 'supplier@maketushop.com'],
            [
                'name' => 'Vendeur Test',
                'username' => 'supplier_test',
                'role' => User::ROLE_SUPPLIER,
                'phone' => '237622222222',
                'address' => 'Yaoundé, Cameroun',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
            ]
        );

        // 3. Regular User (Client)
        User::updateOrCreate(
            ['email' => 'client@maketushop.com'],
            [
                'name' => 'Client Test',
                'username' => 'client_test',
                'role' => User::ROLE_USER,
                'phone' => '237633333333',
                'address' => 'Bafoussam, Cameroun',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
            ]
        );

        // --- END OF TEST USERS ---

        // Create suppliers
        $supplierCount = 10;
        for ($i = 0; $i < $supplierCount; $i++) {
            $name = fake()->name();
            $username = Str::slug($name, '_') . '_' . Str::random(4);

            User::factory()->create([
                'name' => $name,
                'username' => $username,
                'email' => fake()->unique()->safeEmail(),
                'role' => User::ROLE_SUPPLIER,
                'phone' => fake()->numerify('2376########'),
                'address' => fake()->address(),
                'email_verified_at' => now(),
            ]);
        }

        // Create regular users
        $userCount = 20;
        for ($i = 0; $i < $userCount; $i++) {
            $name = fake()->name();
            $username = Str::slug($name, '_') . '_' . Str::random(4);

            User::factory()->create([
                'name' => $name,
                'username' => $username,
                'email' => fake()->unique()->safeEmail(),
                'role' => User::ROLE_USER,
                'phone' => fake()->numerify('2376########'),
                'address' => fake()->address(),
                'email_verified_at' => now(),
            ]);
        }
    }
}
