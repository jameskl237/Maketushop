<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\Product;
use Illuminate\Database\Seeder;

class TestMediaSeeder extends Seeder
{
    // Curated Picsum photo IDs that look like marketplace products
    private const PRODUCT_PHOTOS = [
        237, 1, 20, 26, 28, 36, 39, 42, 56, 63,
        64, 65, 74, 83, 91, 96, 100, 110, 119, 130,
        133, 137, 146, 152, 154, 159, 163, 167, 169, 180,
        188, 190, 200, 201, 218, 219, 225, 227, 229, 230,
        234, 239, 243, 247, 248, 250, 251, 258, 260, 261,
    ];

    public function run(): void
    {
        $products = Product::all();
        $photoIds = self::PRODUCT_PHOTOS;

        foreach ($products as $idx => $product) {
            // Remove existing placeholder media
            $product->medias()->delete();

            $count = rand(2, 4);
            for ($i = 0; $i < $count; $i++) {
                $photoId = $photoIds[($idx * 3 + $i) % count($photoIds)];
                Media::create([
                    'product_id' => $product->id,
                    'url' => "https://picsum.photos/id/{$photoId}/600/600",
                    'type' => 'image',
                    'is_principal' => $i === 0,
                ]);
            }
        }
    }
}
