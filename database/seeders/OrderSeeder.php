<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $products = Product::all();
        if ($users->isEmpty() || $products->isEmpty()) {
            return;
        }

        $ordersToCreate = 30;

        for ($i = 0; $i < $ordersToCreate; $i++) {
            $user = $users->random();
            $order = Order::factory()->create([
                'user_id' => $user->id,
            ]);

            $itemsCount = rand(1, 4);
            $totalProducts = 0;
            $totalPrice = 0;

            $selected = $products->random($itemsCount);
            foreach ($selected as $product) {
                $qty = rand(1, 3);
                $price = (float) $product->price;
                $order->products()->attach($product->id, [
                    'quantity' => $qty,
                    'price' => $price,
                ]);

                $totalProducts += $qty;
                $totalPrice += $price * $qty;
            }

            $order->total_products = $totalProducts;
            $order->total_price = $totalPrice;
            $order->save();
        }
    }
}
