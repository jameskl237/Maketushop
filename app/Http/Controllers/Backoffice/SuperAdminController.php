<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shop;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Collection;

class SuperAdminController extends Controller
{
    public function accounting(Request $request)
    {
        // Total revenue (delivered orders)
        $totalRevenue = Order::where('status', Order::STATUS_DELIVERED)->sum('total_price');

        // Total orders
        $totalOrders = Order::count();

        // Platform fees (10%)
        $platformFeePercent = config('payments.platform_fee_percent', 10);
        $totalPlatformFees = (float) $totalRevenue * ($platformFeePercent / 100);

        // Revenue by category
        $revenueByCategory = DB::table('order_product')
            ->join('orders', 'order_product.order_id', '=', 'orders.id')
            ->join('products', 'order_product.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->where('orders.status', Order::STATUS_DELIVERED)
            ->select('categories.name', DB::raw('SUM(order_product.quantity * order_product.price) as total'))
            ->groupBy('categories.id', 'categories.name')
            ->get();

        // Revenue by payment method
        $revenueByPaymentMethod = Order::where('status', Order::STATUS_DELIVERED)
            ->select('payment_method', DB::raw('SUM(total_price) as total'))
            ->groupBy('payment_method')
            ->get()
            ->map(function ($row) {
                $method = $row->payment_method ?: 'Autre';
                if ($method === 'cm.mtn') $method = 'MTN MoMo';
                if ($method === 'cm.orange') $method = 'Orange Money';
                return [
                    'method' => $method,
                    'total' => (float) $row->total
                ];
            });

        // Orders by status
        $ordersByStatus = Order::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        // Revenue over time (daily, last 30 days)
        $dailyRevenue = Order::where('status', Order::STATUS_DELIVERED)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw("DATE(created_at) as date, SUM(total_price) as total")
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->mapWithKeys(function ($row) {
                return [$row->date => (float) $row->total];
            });

        $dailyLabels = collect();
        for ($i = 29; $i >= 0; $i--) {
            $dailyLabels->push(now()->subDays($i)->format('Y-m-d'));
        }

        $revenueOverTime = [
            'labels' => $dailyLabels->all(),
            'datasets' => [
                [
                    'label' => 'Revenu Quotidien',
                    'data' => $dailyLabels->map(fn($d) => $dailyRevenue->get($d, 0))->values()->all(),
                ],
            ],
        ];

        // Top shops by revenue
        $topShops = DB::table('order_product')
            ->join('orders', 'order_product.order_id', '=', 'orders.id')
            ->join('products', 'order_product.product_id', '=', 'products.id')
            ->join('shops', 'products.shop_id', '=', 'shops.id')
            ->where('orders.status', Order::STATUS_DELIVERED)
            ->select('shops.name', DB::raw('SUM(order_product.quantity * order_product.price) as total'))
            ->groupBy('shops.id', 'shops.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return Inertia::render('Backoffice/SuperAdmin/Accounting', [
            'stats' => [
                'total_revenue' => (float) $totalRevenue,
                'total_orders' => $totalOrders,
                'total_platform_fees' => $totalPlatformFees,
                'active_shops' => Shop::count(),
            ],
            'revenue_by_category' => $revenueByCategory,
            'revenue_by_payment_method' => $revenueByPaymentMethod,
            'orders_by_status' => $ordersByStatus,
            'revenue_over_time' => $revenueOverTime,
            'top_shops' => $topShops,
        ]);
    }

    public function dashboard(Request $request)
    {
        // Total revenue (delivered orders)
        $totalRevenue = Order::where('status', Order::STATUS_DELIVERED)->sum('total_price');

        // Total orders
        $totalOrders = Order::count();

        // Recent orders (last 10)
        $recentOrders = Order::with('user')->orderBy('created_at', 'desc')->limit(10)->get();

        // Payout approximation: assume suppliers receive 90% of order total; platform fee 10%
        $platformFeePercent = config('payments.platform_fee_percent', 10);
        $payoutPercent = max(0, 100 - $platformFeePercent);

        $totalPayouts = (float) $totalRevenue * ($payoutPercent / 100);

        $totalPlatformFees = $totalRevenue - $totalPayouts;

        // Balances per shop: sum of delivered order amounts grouped by shop via order_product
        $shopSums = DB::table('order_product')
            ->join('orders', 'order_product.order_id', '=', 'orders.id')
            ->join('products', 'order_product.product_id', '=', 'products.id')
            ->join('shops', 'products.shop_id', '=', 'shops.id')
            ->where('orders.status', Order::STATUS_DELIVERED)
            ->select('shops.id as shop_id', 'shops.name as shop_name', DB::raw('SUM(order_product.quantity * order_product.price) as total'))
            ->groupBy('shops.id', 'shops.name')
            ->get();

        $balances = $shopSums->map(function ($row) use ($payoutPercent) {
            $shopTotal = (float) $row->total;
            return [
                'id' => $row->shop_id,
                'name' => $row->shop_name,
                'total_sales' => $shopTotal,
                'payable' => (float) ($shopTotal * ($payoutPercent / 100)),
                'withheld' => (float) ($shopTotal * (1 - ($payoutPercent / 100))),
            ];
        });

        // Balances per supplier (sum of delivered orders for shops belonging to each supplier)
        $supplierSums = DB::table('order_product')
            ->join('orders', 'order_product.order_id', '=', 'orders.id')
            ->join('products', 'order_product.product_id', '=', 'products.id')
            ->join('shops', 'products.shop_id', '=', 'shops.id')
            ->join('users', 'shops.user_id', '=', 'users.id')
            ->where('orders.status', Order::STATUS_DELIVERED)
            ->select('users.id as user_id', 'users.name as user_name', DB::raw('SUM(order_product.quantity * order_product.price) as total'))
            ->groupBy('users.id', 'users.name')
            ->get();

        $supplierBalances = $supplierSums->map(function ($row) use ($payoutPercent) {
            $total = (float) $row->total;
            return [
                'id' => $row->user_id,
                'name' => $row->user_name,
                'total_sales' => $total,
                'payable' => (float) ($total * ($payoutPercent / 100)),
                'withheld' => (float) ($total * (1 - ($payoutPercent / 100))),
            ];
        });

        // Monthly revenue dataset (last 6 months)
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';
        $monthFormat = $isSqlite ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')";

        $monthly = Order::selectRaw("$monthFormat as month, SUM(total_price) as total")
            ->where('status', Order::STATUS_DELIVERED)
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->mapWithKeys(function ($row) {
                return [$row->month => (float) $row->total];
            });

        // prepare labels for last 6 months
        $labels = collect();
        for ($i = 5; $i >= 0; $i--) {
            $labels->push(now()->subMonths($i)->format('Y-m'));
        }

        $chartData = [
            'labels' => $labels->all(),
            'datasets' => [
                [
                    'label' => 'Revenu',
                    'data' => $labels->map(fn($m) => $monthly->get($m, 0))->values()->all(),
                ],
            ],
        ];

        return Inertia::render('Backoffice/SuperAdmin/Dashboard', [
            'stats' => [
                'total_revenue' => (float) $totalRevenue,
                'total_orders' => $totalOrders,
                'total_payouts' => (float) $totalPayouts,
                'total_platform_fees' => (float) $totalPlatformFees,
            ],
            'recent_orders' => $recentOrders,
            'balances' => $balances,
            'supplier_balances' => $supplierBalances,
            'chart' => $chartData,
        ]);
    }
}
