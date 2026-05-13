<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Rating;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RatingController extends Controller
{
    public function rateProduct(Request $request, Product $product)
    {
        $data = $request->validate([
            'score' => ['required', 'integer', Rule::in([1, 2, 3, 4, 5])],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        $rating = Rating::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'rateable_id' => $product->id,
                'rateable_type' => Product::class,
            ],
            [
                'score' => $data['score'],
                'comment' => $data['comment'] ?? null,
            ]
        );

        return back()->with('success', 'Note enregistrée.');
    }

    public function rateShop(Request $request, Shop $shop)
    {
        $data = $request->validate([
            'score' => ['required', 'integer', Rule::in([1, 2, 3, 4, 5])],
            'comment' => ['nullable', 'string', 'max:500'],
        ]);

        Rating::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'rateable_id' => $shop->id,
                'rateable_type' => Shop::class,
            ],
            [
                'score' => $data['score'],
                'comment' => $data['comment'] ?? null,
            ]
        );

        return back()->with('success', 'Note enregistrée.');
    }
}
