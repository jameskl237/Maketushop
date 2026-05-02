<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class UserDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        $orders = Order::with('products')
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return Inertia::render('Dashboard', [
            'orders' => $orders
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
