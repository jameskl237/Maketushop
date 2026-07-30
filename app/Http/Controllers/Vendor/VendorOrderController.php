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
        $this->middleware(['auth', 'role:supplier']);
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

    public function deliver(Order $order)
    {
        $this->authorizeVendor($order);
        $this->orderService->markAsDelivered($order, Auth::id());

        return back()->with('success', 'Commande livrée. Le montant a été crédité sur votre solde disponible.');
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
