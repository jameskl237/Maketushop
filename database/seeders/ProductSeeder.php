<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Shop;
use App\Models\Category;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $shops = Shop::all();
        $categoryIds = Category::query()->pluck('id')->all();

        foreach ($shops as $shop) {
            $count = rand(3, 8);
            for ($i = 0; $i < $count; $i++) {
                $catId = $categoryIds[array_rand($categoryIds)];

                Product::factory()->create([
                    'category_id' => $catId,
                    'user_id' => $shop->user_id,
                    'shop_id' => $shop->id,
                ]);
            }
        }
    }
}
