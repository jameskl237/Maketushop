<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Service;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Service::query()
            ->where('is_active', true)
            ->with([
                'category:id,name,slug',
                'shop:id,name,city,phone,logo,user_id',
                'shop.user:id,phone',
                'medias:id,service_id,url,type,is_principal',
            ])
            ->withCount('orders as sold_count')
            ->withAvg('ratings as average_rating', 'score')
            ->withCount('ratings as ratings_count');

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
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
            $query->where(function ($q) use ($locations): void {
                $q->whereIn('city', $locations)
                    ->orWhereHas('shop', function ($shopQuery) use ($locations): void {
                        $shopQuery->whereIn('city', $locations);
                    });
            });
        }

        if ($request->filled('price_min')) {
            $query->whereRaw('CAST(price AS DECIMAL(10,2)) >= ?', [(float) $request->input('price_min')]);
        }

        if ($request->filled('price_max')) {
            $query->whereRaw('CAST(price AS DECIMAL(10,2)) <= ?', [(float) $request->input('price_max')]);
        }

        if ($request->filled('rating_min')) {
            $ratingMin = (float) $request->input('rating_min');
            // On filtre via une sous-requête sur la moyenne des notes
            // (un HAVING ne fonctionne pas avec le count() de pagination sans GROUP BY).
            $query->whereExists(function ($sub) use ($ratingMin): void {
                $sub->selectRaw('1')
                    ->from('ratings')
                    ->whereColumn('ratings.rateable_id', 'services.id')
                    ->where('ratings.rateable_type', Service::class)
                    ->groupBy('ratings.rateable_id')
                    ->havingRaw('AVG(ratings.score) >= ?', [$ratingMin]);
            });
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
            case 'rating':
                $query->orderByDesc('average_rating');
                break;
            default:
                $query->latest();
                break;
        }

        $services = $query
            ->paginate(15)
            ->through(fn (Service $service) => $this->transformService($service))
            ->withQueryString();

        return Inertia::render('Services/Index', [
            'services' => $services,
            'filters' => [
                'search' => (string) $request->input('search', ''),
                'categories' => $categories,
                'locations' => $locations,
                'price_min' => $request->input('price_min'),
                'price_max' => $request->input('price_max'),
                'rating_min' => $request->input('rating_min'),
                'sort' => (string) $request->input('sort', 'newest'),
            ],
            'availableCategories' => Category::query()
                ->whereHas('services')
                ->withCount('services')
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

    public function show(Request $request, Service $service): Response
    {
        $service->load([
            'category:id,name,slug',
            'shop:id,name,city,phone,logo,user_id',
            'shop.user:id,name,phone',
            'medias:id,service_id,url,type,is_principal',
            'ratings' => fn ($q) => $q->with('user:id,name')->latest(),
        ])
            ->loadCount('orders as sold_count')
            ->loadAvg('ratings as average_rating', 'score')
            ->loadCount('ratings as ratings_count');

        $relatedServices = Service::query()
            ->where('is_active', true)
            ->where('category_id', $service->category_id)
            ->whereKeyNot($service->id)
            ->with([
                'category:id,name,slug',
                'shop:id,name,city,phone,logo,user_id',
                'shop.user:id,phone',
                'medias:id,service_id,url,type,is_principal',
            ])
            ->withCount('orders as sold_count')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Service $related) => $this->transformService($related))
            ->values();

        $payload = $this->transformService($service, true);
        $payload['reviews'] = $service->ratings
            ->map(fn ($rating) => [
                'id' => $rating->id,
                'score' => (int) $rating->score,
                'comment' => $rating->comment,
                'author' => $rating->user?->name ?? 'Client',
                'created_at' => optional($rating->created_at)->toDateString(),
            ])
            ->values()
            ->all();

        $userId = $request->user()?->id;
        $payload['user_rating'] = $userId
            ? (int) ($service->ratings->firstWhere('user_id', $userId)?->score ?? 0) ?: null
            : null;

        return Inertia::render('Services/Show', [
            'service' => $payload,
            'relatedServices' => $relatedServices,
        ]);
    }

    public function buy(Service $service): Response
    {
        $service->load([
            'category:id,name,slug',
            'shop:id,name,city,phone,logo,user_id',
            'shop.user:id,name,phone',
            'medias:id,service_id,url,type,is_principal',
        ])->loadCount('orders as sold_count');

        return Inertia::render('Services/Buy', [
            'service' => $this->transformService($service, true),
        ]);
    }

    private function transformService(Service $service, bool $withDetails = false): array
    {
        $images = $service->medias
            ->where('type', 'image')
            ->values()
            ->map(fn ($media) => [
                'id' => $media->id,
                'url' => $media->full_url ?? null,
                'alt' => $service->title,
                'is_main' => (bool) $media->is_principal,
            ])
            ->values();

        $mainImage = optional($images->firstWhere('is_main', true))['url']
            ?? optional($images->first())['url'];

        $quoteOnly = (bool) $service->quote_only || $service->price === null;
        $currentPrice = $quoteOnly ? null : (float) $service->price;

        $payload = [
            'id' => $service->id,
            'name' => $service->title,
            'title' => $service->title,
            'subtitle' => Str::limit((string) $service->description, 80),
            'slug' => Str::slug($service->title).'-'.$service->id,
            'description' => $withDetails ? $service->long_description : null,
            'short_description' => $service->description,
            'current_price' => $currentPrice,
            'quote_only' => $quoteOnly,
            'sold_count' => (int) ($service->sold_count ?? 0),
            'is_new' => optional($service->created_at)->gt(now()->subDays(14)) ?? false,
            'is_favorite' => false,
            'average_rating' => $service->average_rating,
            'ratings_count' => $service->ratings_count,
            'city' => $service->city,
            'category' => $service->category ? [
                'id' => $service->category->id,
                'name' => $service->category->name,
                'slug' => $service->category->slug,
            ] : null,
            'shop' => $service->shop ? [
                'id' => $service->shop->id,
                'name' => $service->shop->name,
                'logo' => $service->shop->logo_url,
                'rating' => $service->shop->average_rating,
                'ratings_count' => $service->shop->ratings_count,
                'city' => $service->shop->city,
                'owner_name' => $withDetails ? $service->shop?->user?->name : null,
                'owner_phone' => $service->shop->phone ?: $service->shop?->user?->phone,
            ] : null,
            'images' => $images,
            'main_image' => $mainImage,
            'reviews' => [],
        ];

        if (!$withDetails) {
            unset($payload['description'], $payload['reviews']);
        }

        return $payload;
    }
}
