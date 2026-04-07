<?php

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;

class ShopSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = User::query()->where('role', User::ROLE_SUPPLIER)->get();

        foreach ($suppliers as $supplier) {
            $count = rand(1, 2);
            Shop::factory()->count($count)->create([
                'user_id' => $supplier->id,
            ]);
        }
    }
}
