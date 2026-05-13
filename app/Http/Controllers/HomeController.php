<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        // Hero slider: featured / newest products with images
        $heroProducts = Product::query()
            ->with(['medias:id,product_id,url,type,is_principal', 'shop:id,name', 'category:id,name'])
            ->withAvg('ratings as average_rating', 'score')
            ->whereHas('medias', fn ($q) => $q->where('type', 'image'))
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (Product $p) => $this->miniProduct($p));

        // Categories with product count
        $categories = Category::withCount('products')
            ->orderByDesc('products_count')
            ->limit(8)
            ->get(['id', 'name', 'slug', 'image']);

        // New arrivals (last 14 days)
        $newArrivals = Product::query()
            ->with(['medias:id,product_id,url,type,is_principal', 'shop:id,name', 'category:id,name'])
            ->withAvg('ratings as average_rating', 'score')
            ->withCount('ratings as ratings_count')
            ->where('created_at', '>=', now()->subDays(14))
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Product $p) => $this->miniProduct($p));

        // Popular products (most sold)
        $popular = Product::query()
            ->with(['medias:id,product_id,url,type,is_principal', 'shop:id,name', 'category:id,name'])
            ->withCount('orders as sold_count')
            ->withAvg('ratings as average_rating', 'score')
            ->withCount('ratings as ratings_count')
            ->orderByDesc('sold_count')
            ->limit(8)
            ->get()
            ->map(fn (Product $p) => $this->miniProduct($p));

        // Top shops
        $shops = Shop::query()
            ->withCount('products')
            ->latest()
            ->get(['id', 'name', 'description', 'city', 'logo'])
            ->filter(fn (Shop $s) => $s->products_count > 0)
            ->take(6)
            ->map(fn (Shop $s) => [
                'id'             => $s->id,
                'name'           => $s->name,
                'description'    => $s->description,
                'city'           => $s->city,
                'logo'           => $s->logo_url,
                'products_count' => $s->products_count,
            ]);

        return Inertia::render('Home', [
            'heroProducts' => $heroProducts,
            'categories'   => $categories,
            'newArrivals'  => $newArrivals,
            'popular'      => $popular,
            'shops'        => $shops,
        ]);
    }

    private function miniProduct(Product $product): array
    {
        $medias = $product->medias->where('type', 'image')->values();
        $mainImage = $medias->firstWhere('is_principal', true)?->full_url
            ?? $medias->first()?->full_url
            ?? '/images/Maketu1.png';

        $price = (float) $product->price;
        $promo = $product->promotion_price ? (float) $product->promotion_price : null;

        return [
            'id'             => $product->id,
            'name'           => $product->name,
            'main_image'     => $mainImage,
            'price'          => $price,
            'promo_price'    => $promo,
            'average_rating' => round((float) ($product->average_rating ?? 0), 1),
            'ratings_count'  => (int) ($product->ratings_count ?? 0),
            'stock'          => (int) $product->quantity,
            'is_new'         => optional($product->created_at)->gt(now()->subDays(14)) ?? false,
            'category'       => $product->category ? ['id' => $product->category->id, 'name' => $product->category->name] : null,
            'shop'           => $product->shop ? ['id' => $product->shop->id, 'name' => $product->shop->name] : null,
        ];
    }
}
