<?php

namespace App\Http\Controllers\Backoffice\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('backoffice.admin.management', ['tab' => 'orders']);
    }

    public function show(Order $order)
    {
        return Inertia::render('Backoffice/Admin/Orders/Show', [
            'order' => $order->load(['user', 'products']),
        ]);
    }

    public function destroy(Order $order)
    {
        $order->delete();

        return redirect()->route('backoffice.admin.management', ['tab' => 'orders'])->with('success', 'Commande supprimée.');
    }
}
