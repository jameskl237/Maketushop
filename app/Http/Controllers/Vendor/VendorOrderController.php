<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Order\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class VendorOrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {
    }

    public function index(Request $request)
    {
        $vendorStatus = $request->query('status');
        $orders = $this->orderService->getVendorOrders(Auth::id(), $vendorStatus);

        return Inertia::render('Backoffice/Vendor/Orders', [
            'orders' => $orders,
            'currentStatus' => $vendorStatus,
            'statuses' => [
                'pending' => 'En attente',
                'accepted' => 'Acceptée',
                'preparing' => 'En préparation',
                'shipped' => 'Expédiée',
                'delivered' => 'Livrée',
            ],
        ]);
    }

    public function show(Order $order)
    {
        $this->authorizeVendor($order);

        $order->load(['products' => function ($q) {
            $q->where('user_id', Auth::id());
        }, 'user']);

        return Inertia::render('Backoffice/Vendor/OrderDetail', [
            'order' => $order,
            'delivery' => [
                'has_proof' => $order->hasDeliveryProof(),
                'proof_url' => $order->delivery_proof_path
                    ? \Illuminate\Support\Facades\Storage::disk('public')->url($order->delivery_proof_path)
                    : null,
                'proof_at' => $order->delivery_proof_at,
                'client_confirmed_at' => $order->client_confirmed_at,
                'is_paid' => $order->isPaid(),
                'status' => $order->status,
            ],
        ]);
    }

    public function accept(Order $order)
    {
        $this->authorizeVendor($order);
        $this->orderService->acceptByVendor($order, Auth::id());

        return back()->with('success', 'Commande acceptée.');
    }

    public function prepare(Order $order)
    {
        $this->authorizeVendor($order);
        $this->orderService->markAsPreparing($order, Auth::id());

        return back()->with('success', 'Commande en préparation.');
    }

    public function ship(Order $order)
    {
        $this->authorizeVendor($order);
        $this->orderService->markAsShipped($order, Auth::id());

        return back()->with('success', 'Commande expédiée.');
    }

    /**
     * Le vendeur atteste la livraison en joignant une photo. Le paiement n'est
     * libéré qu'ensuite, quand le client confirme avoir reçu sa commande.
     */
    public function deliver(Request $request, Order $order)
    {
        $this->authorizeVendor($order);

        $validated = $request->validate([
            'proof' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'proof.required' => 'Une photo de livraison est obligatoire.',
            'proof.image' => 'Le fichier doit être une image.',
            'proof.max' => 'L\'image ne doit pas dépasser 5 Mo.',
        ]);

        try {
            $this->orderService->attachDeliveryProof($order, Auth::id(), $validated['proof']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Preuve de livraison enregistrée. Le paiement sera versé dès que le client aura confirmé la réception.');
    }

    private function authorizeVendor(Order $order): void
    {
        $belongsToVendor = $order->products()
            ->where('user_id', Auth::id())
            ->exists();

        if (!$belongsToVendor) {
            abort(403, 'Cette commande ne vous appartient pas.');
        }
    }
}
