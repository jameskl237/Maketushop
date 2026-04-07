<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'order_number' => 'ORD-' . strtoupper(Str::random(8)),
            'user_id' => null, // set in seeder
            'total_products' => 0,
            'total_price' => 0,
        ];
    }
}
