<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\QuoteRequest;
use App\Models\Service;
use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SupplierServiceController extends Controller
{
    public function index(): Response
    {
        $supplierId = request()->user()->id;

        $services = Service::query()
            ->where('user_id', $supplierId)
            ->with(['shop:id,name', 'category:id,name'])
            ->withCount('medias')
            ->latest()
            ->get([
                'id', 'code', 'title', 'price', 'quote_only', 'is_active',
                'shop_id', 'category_id', 'city', 'created_at',
            ]);

        $shops = request()->user()->shops()->get(['id', 'name']);
        $categories = Category::query()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Backoffice/Supplier/Services', [
            'services' => $services,
            'shops' => $shops,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $shop = $this->resolveOwnedShop($request);

        $validated = $request->validate([
            'shop_id' => ['required', 'exists:shops,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'long_description' => ['nullable', 'string'],
            'quote_only' => ['required', 'boolean'],
            'price' => ['nullable', 'required_if:quote_only,false', 'numeric', 'min:0'],
            'city' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'medias' => ['nullable', 'array', 'max:10'],
            'medias.*' => ['file', 'max:51200', 'mimetypes:image/jpeg,image/png,image/webp,image/gif'],
        ]);

        DB::transaction(function () use ($validated, $request, $shop): void {
            $service = Service::create([
                'code' => 'SVC-'.Str::upper(Str::random(8)),
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'long_description' => $validated['long_description'] ?? null,
                'quote_only' => $validated['quote_only'],
                'price' => $validated['quote_only'] ? null : (string) $validated['price'],
                'city' => $validated['city'] ?? $shop->city,
                'is_active' => $validated['is_active'],
                'category_id' => $validated['category_id'] ?? null,
                'user_id' => $request->user()->id,
                'shop_id' => $shop->id,
            ]);

            foreach ($request->file('medias', []) as $index => $file) {
                $service->medias()->create([
                    'url' => $file->store('services', 'public'),
                    'type' => 'image',
                    'is_principal' => $index === 0,
                ]);
            }
        });

        return redirect()
            ->route('backoffice.supplier.services.index')
            ->with('success', 'Service créé avec succès.');
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $this->ensureOwnsService($service);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'long_description' => ['nullable', 'string'],
            'quote_only' => ['required', 'boolean'],
            'price' => ['nullable', 'required_if:quote_only,false', 'numeric', 'min:0'],
            'city' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'category_id' => ['nullable', 'exists:categories,id'],
        ]);

        $service->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'long_description' => $validated['long_description'] ?? null,
            'quote_only' => $validated['quote_only'],
            'price' => $validated['quote_only'] ? null : (string) $validated['price'],
            'city' => $validated['city'] ?? $service->city,
            'is_active' => $validated['is_active'],
            'category_id' => $validated['category_id'] ?? null,
        ]);

        return redirect()
            ->route('backoffice.supplier.services.index')
            ->with('success', 'Service mis à jour.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $this->ensureOwnsService($service);

        $service->delete();

        return redirect()
            ->route('backoffice.supplier.services.index')
            ->with('success', 'Service supprimé.');
    }

    public function quoteRequests(): Response
    {
        $supplierId = request()->user()->id;

        $requests = QuoteRequest::query()
            ->whereHas('service', function ($query) use ($supplierId): void {
                $query->where('user_id', $supplierId);
            })
            ->with(['service:id,title', 'user:id,name'])
            ->latest()
            ->get();

        return Inertia::render('Backoffice/Supplier/QuoteRequests', [
            'quoteRequests' => $requests,
        ]);
    }

    public function markQuoteHandled(QuoteRequest $quoteRequest): RedirectResponse
    {
        abort_unless(
            $quoteRequest->service?->user_id === request()->user()->id,
            403,
        );

        $quoteRequest->update(['status' => QuoteRequest::STATUS_HANDLED]);

        return back()->with('success', 'Demande marquée comme traitée.');
    }

    private function resolveOwnedShop(Request $request): Shop
    {
        $shop = Shop::findOrFail($request->input('shop_id'));
        abort_unless($shop->user_id === $request->user()->id, 403);

        return $shop;
    }

    private function ensureOwnsService(Service $service): void
    {
        abort_unless($service->user_id === request()->user()->id, 403);
    }
}
