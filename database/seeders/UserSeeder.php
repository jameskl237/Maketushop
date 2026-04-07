<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
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
