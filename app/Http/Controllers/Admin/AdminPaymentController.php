<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CinetpayTransaction;
use App\Models\Order;
use App\Models\Payment;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminPaymentController extends Controller
{
    // Authentification et rôle appliqués par le groupe de routes
    // (Laravel 12 : le contrôleur de base ne porte plus de middleware).
    public function orders()
    {
        return Inertia::render('Backoffice/Admin/Payments/Orders', [
            'orders' => Order::with(['user', 'products'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function orderShow(Order $order)
    {
        $order->load(['user', 'products.shop', 'products.medias', 'payment']);

        return Inertia::render('Backoffice/Admin/Payments/OrderDetail', [
            'order' => $order,
        ]);
    }

    public function transactions()
    {
        return Inertia::render('Backoffice/Admin/Payments/Transactions', [
            'payments' => Payment::with('payable')
                ->latest()
                ->paginate(20),
        ]);
    }

    public function cinetpayTransactions()
    {
        return Inertia::render('Backoffice/Admin/Payments/CinetpayTransactions', [
            'transactions' => CinetpayTransaction::latest()->paginate(20),
        ]);
    }

    public function webhookLogs()
    {
        return Inertia::render('Backoffice/Admin/Payments/WebhookLogs', [
            'logs' => WebhookLog::latest()->paginate(20),
        ]);
    }
}
