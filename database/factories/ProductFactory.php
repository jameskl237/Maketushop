<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $price = fake()->randomFloat(2, 100, 20000);

        return [
            'code' => Str::upper(Str::random(8)),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'long_description' => fake()->paragraph(),
            'price' => (string) $price,
            'promotion_price' => null,
            'in_stock' => fake()->boolean(80),
            'quantity' => fake()->randomFloat(2, 1, 100),
            'origin' => fake()->randomElement(['local', 'imported']),
            // category_id, user_id, shop_id will be supplied by seeder
        ];
    }
}
