<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SuperAdminSeeder extends Seeder
{
    /**
     * Seed a super admin user.
     */
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL', 'superadmin@maketu.com');
        $password = env('SUPER_ADMIN_PASSWORD', env('ADMIN_SEED_PASSWORD', 'ChangeMe@123'));
        $username = env('SUPER_ADMIN_USERNAME', 'superadmin');
        $name = env('SUPER_ADMIN_NAME', 'Super Admin');

        // Ensure username uniqueness: if username is taken by another email, append suffix.
        $base = Str::slug($username, '_') ?: 'superadmin';
        $finalUsername = $base;
        $counter = 1;
        while (User::where('username', $finalUsername)->where('email', '!=', $email)->exists()) {
            $finalUsername = $base . '_' . $counter++;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'username' => $finalUsername,
                'role' => User::ROLE_SUPERADMIN,
                'phone' => env('SUPER_ADMIN_PHONE', null),
                'address' => env('SUPER_ADMIN_ADDRESS', null),
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        // Ensure the role is superadmin (in case an existing account had a different role)
        if ($user->role !== User::ROLE_SUPERADMIN) {
            $user->role = User::ROLE_SUPERADMIN;
            $user->save();
        }
    }
}
