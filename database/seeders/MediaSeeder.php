<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\Product;
use Illuminate\Database\Seeder;

class MediaSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all();

        foreach ($products as $product) {
            $count = rand(1, 3);
            for ($i = 0; $i < $count; $i++) {
                Media::factory()->create([
                    'product_id' => $product->id,
                    'is_principal' => $i === 0,
                ]);
            }
        }
    }
}
