<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UserDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $orders = Order::with(['products', 'services'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        // Favoris produits
        $favoriteProducts = Product::query()
            ->whereIn('id', $user->favoriteProductIds())
            ->with([
                'shop:id,name,city,logo',
                'medias:id,product_id,url,type,is_principal',
            ])
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'type' => 'product',
                'name' => $p->name,
                'price' => $p->price,
                'shop' => $p->shop?->name,
                'image' => optional($p->medias->firstWhere('is_principal', true) ?? $p->medias->first())?->full_url,
            ]);

        // Favoris services
        $favoriteServices = Service::query()
            ->whereIn('id', $user->favoriteServiceIds())
            ->with([
                'shop:id,name,city,logo',
                'medias:id,service_id,url,type,is_principal',
            ])
            ->get()
            ->map(fn (Service $s) => [
                'id' => $s->id,
                'type' => 'service',
                'name' => $s->title,
                'price' => $s->quote_only ? null : $s->price,
                'quote_only' => (bool) $s->quote_only,
                'shop' => $s->shop?->name,
                'image' => optional($s->medias->firstWhere('is_principal', true) ?? $s->medias->first())?->full_url,
            ]);

        // Demandes de devis envoyées
        $quoteRequests = QuoteRequest::query()
            ->where('user_id', $user->id)
            ->with('service:id,title,shop_id')
            ->latest()
            ->get()
            ->map(fn (QuoteRequest $q) => [
                'id' => $q->id,
                'service_id' => $q->service_id,
                'service_title' => $q->service?->title,
                'budget' => $q->budget,
                'message' => $q->message,
                'status' => $q->status,
                'created_at' => $q->created_at,
            ]);

        return Inertia::render('Dashboard', [
            'orders' => $orders,
            'favorites' => $favoriteProducts->concat($favoriteServices)->values(),
            'quoteRequests' => $quoteRequests,
        ]);
    }

    public function markAsDelivered(Order $order)
    {
        // Ensure the order belongs to the user
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        $order->update(['status' => Order::STATUS_DELIVERED]);

        return back()->with('success', 'La commande a été marquée comme livrée.');
    }
}
