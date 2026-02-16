<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function shops(Request $request): Response
    {
        $shops = Shop::query()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->input('search'));
                $query->where(function ($innerQuery) use ($search): void {
                    $innerQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('district', 'like', "%{$search}%");
                });
            })
            ->withCount('products')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Shops/Index', [
            'shops' => $shops,
            'filters' => [
                'search' => (string) $request->input('search', ''),
            ],
        ]);
    }

    public function cart(): Response
    {
        return Inertia::render('Cart/Index');
    }

    public function cartMetadata(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer', 'exists:products,id'],
        ]);

        $products = Product::query()
            ->whereIn('id', $validated['ids'])
            ->with([
                'shop:id,name,logo,phone,user_id',
                'shop.user:id,phone',
            ])
            ->get(['id', 'shop_id']);

        $metadata = $products->mapWithKeys(function (Product $product) {
            return [
                $product->id => [
                    'shop' => [
                        'id' => $product->shop?->id,
                        'name' => $product->shop?->name ?? 'Boutique inconnue',
                        'logo' => $product->shop?->logo_url,
                        'owner_phone' => $product->shop?->phone ?: $product->shop?->user?->phone,
                    ],
                ],
            ];
        });

        return response()->json([
            'metadata' => $metadata,
        ]);
    }

    public function index(Request $request): Response
    {
        $query = Product::query()
            ->with([
                'category:id,name,slug',
                'shop:id,name,city,phone,logo,user_id',
                'shop.user:id,phone',
                'medias:id,product_id,url,type,is_principal',
            ])
            ->withCount('orders as sold_count');

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('long_description', 'like', "%{$search}%")
                    ->orWhereHas('category', function ($categoryQuery) use ($search): void {
                        $categoryQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $categories = array_filter((array) $request->input('categories', []));
        if (!empty($categories)) {
            $query->whereIn('category_id', $categories);
        }

        $locations = array_filter((array) $request->input('locations', []));
        if (!empty($locations)) {
            $query->whereHas('shop', function ($shopQuery) use ($locations): void {
                $shopQuery->whereIn('city', $locations);
            });
        }

        if ($request->filled('price_min')) {
            $query->whereRaw('CAST(price AS DECIMAL(10,2)) >= ?', [(float) $request->input('price_min')]);
        }

        if ($request->filled('price_max')) {
            $query->whereRaw('CAST(price AS DECIMAL(10,2)) <= ?', [(float) $request->input('price_max')]);
        }

        if ($request->boolean('in_stock')) {
            $query->where('in_stock', true)
                ->where('quantity', '>', 0);
        }

        switch ($request->input('sort', 'newest')) {
            case 'price_asc':
                $query->orderByRaw('CAST(price AS DECIMAL(10,2)) ASC');
                break;
            case 'price_desc':
                $query->orderByRaw('CAST(price AS DECIMAL(10,2)) DESC');
                break;
            case 'popular':
                $query->orderByDesc('sold_count');
                break;
            default:
                $query->latest();
                break;
        }

        $products = $query
            ->paginate(15)
            ->through(fn (Product $product) => $this->transformProduct($product))
            ->withQueryString();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'filters' => [
                'search' => (string) $request->input('search', ''),
                'categories' => $categories,
                'locations' => $locations,
                'price_min' => $request->input('price_min'),
                'price_max' => $request->input('price_max'),
                'in_stock' => $request->boolean('in_stock'),
                'sort' => (string) $request->input('sort', 'newest'),
            ],
            'availableCategories' => Category::query()
                ->withCount('products')
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
            'availableLocations' => Shop::query()
                ->select('city')
                ->whereNotNull('city')
                ->distinct()
                ->orderBy('city')
                ->pluck('city'),
        ]);
    }

    public function shopShow(Request $request, Shop $shop): Response
    {
        $shop->loadCount('products');

        $query = Product::query()
            ->where('shop_id', $shop->id)
            ->with([
                'category:id,name,slug',
                'shop:id,name,city,phone,logo,user_id',
                'shop.user:id,phone',
                'medias:id,product_id,url,type,is_principal',
            ])
            ->withCount('orders as sold_count');

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('long_description', 'like', "%{$search}%");
            });
        }

        $categories = array_filter((array) $request->input('categories', []));
        if (!empty($categories)) {
            $query->whereIn('category_id', $categories);
        }

        if ($request->filled('price_min')) {
            $query->whereRaw('CAST(price AS DECIMAL(10,2)) >= ?', [(float) $request->input('price_min')]);
        }

        if ($request->filled('price_max')) {
            $query->whereRaw('CAST(price AS DECIMAL(10,2)) <= ?', [(float) $request->input('price_max')]);
        }

        if ($request->boolean('in_stock')) {
            $query->where('in_stock', true)->where('quantity', '>', 0);
        }

        $products = $query
            ->latest()
            ->paginate(15)
            ->through(fn (Product $product) => $this->transformProduct($product))
            ->withQueryString();

        $availableCategories = Category::query()
            ->whereHas('products', function ($categoryProductsQuery) use ($shop): void {
                $categoryProductsQuery->where('shop_id', $shop->id);
            })
            ->withCount([
                'products as products_count' => function ($categoryProductsCountQuery) use ($shop): void {
                    $categoryProductsCountQuery->where('shop_id', $shop->id);
                },
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return Inertia::render('Shops/Show', [
            'shop' => [
                'id' => $shop->id,
                'name' => $shop->name,
                'description' => $shop->description,
                'city' => $shop->city,
                'district' => $shop->district,
                'logo' => $shop->logo_url,
                'banner_image' => null,
                'tagline' => $shop->description ? Str::limit($shop->description, 80) : null,
                'verified' => true,
                'products_count' => (int) $shop->products_count,
                'rating' => 4.8,
                'reviews_count' => 0,
                'response_time' => '< 1h',
                'phone' => $shop->phone,
            ],
            'products' => $products,
            'filters' => [
                'search' => (string) $request->input('search', ''),
                'categories' => $categories,
                'price_min' => $request->input('price_min'),
                'price_max' => $request->input('price_max'),
                'in_stock' => $request->boolean('in_stock'),
            ],
            'availableCategories' => $availableCategories,
        ]);
    }

    public function show(Product $product): Response
    {
        $product->load([
            'category:id,name,slug',
            'shop:id,name,city,phone,logo,user_id',
            'shop.user:id,name,phone',
            'medias:id,product_id,url,type,is_principal',
        ])->loadCount('orders as sold_count');

        $relatedProducts = Product::query()
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->with([
                'category:id,name,slug',
                'shop:id,name,city,phone,logo,user_id',
                'shop.user:id,phone',
                'medias:id,product_id,url,type,is_principal',
            ])
            ->withCount('orders as sold_count')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Product $related) => $this->transformProduct($related))
            ->values();

        return Inertia::render('Products/Show', [
            'product' => $this->transformProduct($product, true),
            'relatedProducts' => $relatedProducts,
        ]);
    }

    public function buy(Product $product): Response
    {
        $product->load([
            'category:id,name,slug',
            'shop:id,name,city,phone,logo,user_id',
            'shop.user:id,name,phone',
            'medias:id,product_id,url,type,is_principal',
        ])->loadCount('orders as sold_count');

        return Inertia::render('Products/Buy', [
            'product' => $this->transformProduct($product, true),
        ]);
    }

    public function paymentUnavailable(): Response
    {
        return Inertia::render('Payments/Unavailable');
    }

    private function transformProduct(Product $product, bool $withDetails = false): array
    {
        $images = $product->medias
            ->where('type', 'image')
            ->values()
            ->map(fn ($media) => [
                'id' => $media->id,
                'url' => $media->full_url ?? null,
                'alt' => $product->name,
                'is_main' => (bool) $media->is_principal,
            ])
            ->values();

        $mainImage = optional($images->firstWhere('is_main', true))['url']
            ?? optional($images->first())['url'];

        $currentPrice = (float) $product->price;
        $originalPrice = $product->promotion_price ? (float) $product->promotion_price : null;
        $discountPercentage = null;

        if ($originalPrice && $originalPrice > $currentPrice && $originalPrice > 0) {
            $discountPercentage = (int) round((($originalPrice - $currentPrice) / $originalPrice) * 100);
        } else {
            $originalPrice = null;
        }

        $payload = [
            'id' => $product->id,
            'name' => $product->name,
            'subtitle' => Str::limit((string) $product->description, 80),
            'slug' => Str::slug($product->name).'-'.$product->id,
            'description' => $withDetails ? $product->long_description : null,
            'short_description' => $product->description,
            'current_price' => $currentPrice,
            'original_price' => $originalPrice,
            'discount_percentage' => $discountPercentage,
            'stock' => (int) $product->quantity,
            'sold_count' => (int) ($product->sold_count ?? 0),
            'is_new' => optional($product->created_at)->gt(now()->subDays(14)) ?? false,
            'is_favorite' => false,
            'average_rating' => 4.6,
            'reviews_count' => 0,
            'delivery_time' => '24-72h',
            'delivery_cost' => 1000,
            'free_delivery_threshold' => 10000,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
                'slug' => $product->category->slug,
            ] : null,
            'shop' => $product->shop ? [
                'id' => $product->shop->id,
                'name' => $product->shop->name,
                'logo' => $product->shop->logo_url,
                'rating' => 4.8,
                'city' => $product->shop->city,
                'owner_name' => $withDetails ? $product->shop?->user?->name : null,
                'owner_phone' => $product->shop->phone ?: $product->shop?->user?->phone,
            ] : null,
            'images' => $images,
            'main_image' => $mainImage,
            'variants' => [],
            'specifications' => [
                'Origine' => $product->origin === 'imported' ? 'Importe' : 'Local',
                'Reference' => $product->code,
                'Disponibilite' => $product->in_stock ? 'En stock' : 'Rupture',
            ],
            'quick_features' => [
                ['icon' => 'ShieldCheck', 'label' => 'Qualite verifiee'],
                ['icon' => 'Truck', 'label' => 'Livraison rapide'],
                ['icon' => 'Store', 'label' => 'Vendeur local'],
            ],
            'reviews' => [],
        ];

        if (!$withDetails) {
            unset($payload['description'], $payload['specifications'], $payload['quick_features'], $payload['reviews']);
        }

        return $payload;
    }
}

