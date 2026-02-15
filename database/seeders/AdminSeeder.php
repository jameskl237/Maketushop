<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Seed default admin account.
     *
     * Update these values to your real admin profile before production use.
     */
    public function run(): void
    {
        $adminData = [
            // TODO: Remplir avec les infos admin réelles.
            'name' => 'Maketu Admin',
            'username' => 'maketu_admin',
            'email' => 'admin@maketu.com',
            'phone' => '237695988879',
            'address' => 'Yaounde / Nkooabang',
        ];

        $password = env('ADMIN_SEED_PASSWORD', 'ChangeMe@123');

        $adminAlreadyExists = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->orWhere('email', $adminData['email'])
            ->orWhere('username', $adminData['username'])
            ->exists();

        if ($adminAlreadyExists) {
            return;
        }

        User::create([
            'name' => $adminData['name'],
            'username' => $adminData['username'],
            'email' => $adminData['email'],
            'role' => User::ROLE_ADMIN,
            'phone' => $adminData['phone'],
            'address' => $adminData['address'],
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);
    }
}

