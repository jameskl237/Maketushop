<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Media;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function dashboard(): Response
    {
        $supplierId = request()->user()->id;

        $shops = request()->user()
            ->shops()
            ->latest()
            ->get(['id', 'name', 'description', 'city', 'district', 'phone', 'logo', 'created_at']);

        $publishedProductsCount = Product::query()
            ->whereHas('shop', function ($query) use ($supplierId) {
                $query->where('user_id', $supplierId);
            })
            ->count();

        return Inertia::render('Backoffice/Supplier/Dashboard', [
            'shops' => $shops,
            'publishedProductsCount' => $publishedProductsCount,
        ]);
    }

    public function shops(): Response
    {
        $shops = request()->user()
            ->shops()
            ->withCount('products')
            ->latest()
            ->get(['id', 'name', 'description', 'city', 'district', 'phone', 'logo', 'created_at']);

        return Inertia::render('Backoffice/Supplier/Shops', [
            'shops' => $shops,
        ]);
    }

    public function products(): Response
    {
        $supplierId = request()->user()->id;

        $products = Product::query()
            ->whereHas('shop', function ($query) use ($supplierId) {
                $query->where('user_id', $supplierId);
            })
            ->with([
                'shop:id,name',
                'category:id,name',
            ])
            ->withCount('medias')
            ->latest()
            ->get([
                'id',
                'code',
                'name',
                'price',
                'in_stock',
                'quantity',
                'shop_id',
                'category_id',
                'created_at',
            ]);

        return Inertia::render('Backoffice/Supplier/Products', [
            'products' => $products,
        ]);
    }

    public function orders(): Response
    {
        $supplierId = request()->user()->id;

        $orders = Order::query()
            ->whereHas('products.shop', function ($query) use ($supplierId) {
                $query->where('user_id', $supplierId);
            })
            ->with([
                'user:id,name,email',
                'products' => function ($query) use ($supplierId) {
                    $query
                        ->select('products.id', 'products.name', 'products.shop_id')
                        ->whereHas('shop', function ($shopQuery) use ($supplierId) {
                            $shopQuery->where('user_id', $supplierId);
                        })
                        ->with('shop:id,name');
                },
            ])
            ->latest()
            ->get([
                'id',
                'order_number',
                'user_id',
                'total_products',
                'total_price',
                'created_at',
            ])
            ->map(function (Order $order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->user?->name,
                    'customer_email' => $order->user?->email,
                    'total_products' => $order->total_products,
                    'total_price' => $order->total_price,
                    'created_at' => $order->created_at,
                    'supplier_items_count' => $order->products->sum(function (Product $product) {
                        return (int) ($product->pivot->quantity ?? 0);
                    }),
                    'items' => $order->products->map(function (Product $product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'shop_id' => $product->shop_id,
                            'shop_name' => $product->shop?->name,
                            'quantity' => (int) ($product->pivot->quantity ?? 0),
                            'price' => $product->pivot->price ?? null,
                        ];
                    })->values(),
                ];
            })
            ->values();

        return Inertia::render('Backoffice/Supplier/Orders', [
            'orders' => $orders,
        ]);
    }

    public function showShop(Shop $shop): Response
    {
        $this->ensureSupplierOwnsShop($shop);

        $shop->load([
            'products' => function ($query) {
                $query->with(['category:id,name', 'medias:id,product_id,url,type,is_principal'])
                    ->latest();
            },
        ]);

        $products = $shop->products->values();
        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Backoffice/Supplier/ShopProducts', [
            'shop' => $shop->only(['id', 'name', 'description', 'city', 'district']),
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    public function storeShop(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1500'],
            'city' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:5120'],
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('shops', 'public');
        }

        $request->user()->shops()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'city' => $validated['city'],
            'district' => $validated['district'],
            'logo' => $logoPath,
        ]);

        return redirect()
            ->route('backoffice.supplier.dashboard')
            ->with('success', 'Boutique creee avec succes.');
    }

    public function updateShop(Request $request, Shop $shop): RedirectResponse
    {
        $this->ensureSupplierOwnsShop($shop);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1500'],
            'city' => ['required', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'logo' => ['nullable', 'image', 'max:5120'],
        ]);

        $shopData = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'city' => $validated['city'],
            'district' => $validated['district'],
            'phone' => filled($validated['phone'] ?? null) ? trim((string) $validated['phone']) : null,
        ];

        if ($request->hasFile('logo')) {
            if ($shop->logo) {
                Storage::disk('public')->delete($shop->logo);
            }
            $shopData['logo'] = $request->file('logo')->store('shops', 'public');
        }

        $shop->update($shopData);

        return redirect()
            ->route('backoffice.supplier.dashboard')
            ->with('success', 'Boutique mise a jour avec succes.');
    }

    public function storeProduct(Request $request, Shop $shop): RedirectResponse
    {
        $this->ensureSupplierOwnsShop($shop);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'long_description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'promotion_price' => ['nullable', 'numeric', 'min:0'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'origin' => ['required', 'in:local,imported'],
            'in_stock' => ['required', 'boolean'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'medias' => ['nullable', 'array', 'max:10'],
            'medias.*' => ['file', 'max:51200', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/quicktime,video/x-msvideo,video/webm,video/x-matroska'],
        ]);

        DB::transaction(function () use ($validated, $request, $shop): void {
            $product = Product::create([
                'code' => 'PRD-'.Str::upper(Str::random(8)),
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'long_description' => $validated['long_description'] ?? null,
                'price' => (string) $validated['price'],
                'promotion_price' => isset($validated['promotion_price']) ? (string) $validated['promotion_price'] : null,
                'in_stock' => $validated['in_stock'],
                'origin' => $validated['origin'],
                'quantity' => $validated['quantity'],
                'category_id' => $validated['category_id'] ?? null,
                'user_id' => $request->user()->id,
                'shop_id' => $shop->id,
            ]);

            $files = $request->file('medias', []);

            foreach ($files as $index => $file) {
                $path = $file->store('products', 'public');
                $mimeType = (string) $file->getMimeType();

                $product->medias()->create([
                    'url' => $path,
                    'type' => str_starts_with($mimeType, 'video/') ? 'video' : 'image',
                    'is_principal' => $index === 0,
                ]);
            }
        });

        return redirect()
            ->route('backoffice.supplier.shops.show', ['shop' => $shop->id])
            ->with('success', 'Produit cree avec succes.');
    }

    public function showProduct(Shop $shop, Product $product): Response
    {
        $this->ensureSupplierOwnsProduct($shop, $product);

        $product->load([
            'category:id,name',
            'medias:id,product_id,url,type,is_principal',
        ]);

        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Backoffice/Supplier/ProductShow', [
            'shop' => $shop->only(['id', 'name', 'city', 'district']),
            'product' => $product,
            'categories' => $categories,
        ]);
    }

    public function updateProduct(Request $request, Shop $shop, Product $product): RedirectResponse
    {
        $this->ensureSupplierOwnsProduct($shop, $product);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'long_description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'promotion_price' => ['nullable', 'numeric', 'min:0'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'origin' => ['required', 'in:local,imported'],
            'in_stock' => ['required', 'boolean'],
            'category_id' => ['nullable', 'exists:categories,id'],
        ]);

        $product->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'long_description' => $validated['long_description'] ?? null,
            'price' => (string) $validated['price'],
            'promotion_price' => isset($validated['promotion_price']) ? (string) $validated['promotion_price'] : null,
            'quantity' => $validated['quantity'],
            'origin' => $validated['origin'],
            'in_stock' => $validated['in_stock'],
            'category_id' => $validated['category_id'] ?? null,
        ]);

        return redirect()
            ->route('backoffice.supplier.shops.products.show', ['shop' => $shop->id, 'product' => $product->id])
            ->with('success', 'Produit mis a jour avec succes.');
    }

    public function destroyProduct(Shop $shop, Product $product): RedirectResponse
    {
        $this->ensureSupplierOwnsProduct($shop, $product);

        foreach ($product->medias as $media) {
            if ($media->url) {
                Storage::disk('public')->delete($media->url);
            }
        }

        $product->delete();

        return redirect()
            ->route('backoffice.supplier.shops.show', ['shop' => $shop->id])
            ->with('success', 'Produit supprime avec succes.');
    }

    public function storeProductMedias(Request $request, Shop $shop, Product $product): RedirectResponse
    {
        $this->ensureSupplierOwnsProduct($shop, $product);

        $validated = $request->validate([
            'medias' => ['required', 'array', 'min:1', 'max:10'],
            'medias.*' => ['file', 'max:51200', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/quicktime,video/x-msvideo,video/webm,video/x-matroska'],
        ]);

        $files = $request->file('medias', []);
        $hasPrincipal = $product->medias()->where('is_principal', true)->exists();

        foreach ($files as $index => $file) {
            $path = $file->store('products', 'public');
            $mimeType = (string) $file->getMimeType();

            $product->medias()->create([
                'url' => $path,
                'type' => str_starts_with($mimeType, 'video/') ? 'video' : 'image',
                'is_principal' => ! $hasPrincipal && $index === 0,
            ]);
        }

        return redirect()
            ->route('backoffice.supplier.shops.products.show', ['shop' => $shop->id, 'product' => $product->id])
            ->with('success', 'Medias ajoutes avec succes.');
    }

    public function destroyProductMedia(Shop $shop, Product $product, Media $media): RedirectResponse
    {
        $this->ensureSupplierOwnsProduct($shop, $product);
        abort_unless($media->product_id === $product->id, 404);

        if ($media->url) {
            Storage::disk('public')->delete($media->url);
        }

        $wasPrincipal = (bool) $media->is_principal;
        $media->delete();

        if ($wasPrincipal) {
            $firstMedia = $product->medias()->first();
            if ($firstMedia) {
                $firstMedia->update(['is_principal' => true]);
            }
        }

        return redirect()
            ->route('backoffice.supplier.shops.products.show', ['shop' => $shop->id, 'product' => $product->id])
            ->with('success', 'Media supprime avec succes.');
    }

    public function categories(): JsonResponse
    {
        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'categories' => $categories,
        ]);
    }

    private function ensureSupplierOwnsShop(Shop $shop): void
    {
        abort_unless($shop->user_id === request()->user()->id, 403);
    }

    private function ensureSupplierOwnsProduct(Shop $shop, Product $product): void
    {
        $this->ensureSupplierOwnsShop($shop);

        abort_unless((int) $product->shop_id === (int) $shop->id, 403);
    }
}

