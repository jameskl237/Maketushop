<?php

namespace App\Http\Controllers\Backoffice\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function index(): Response
    {
        $stats = [
            'users_total' => User::count(),
            'users_admin' => User::where('role', User::ROLE_ADMIN)->count(),
            'users_supplier' => User::where('role', User::ROLE_SUPPLIER)->count(),
            'shops' => Shop::count(),
            'products' => Product::count(),
            'categories' => Category::count(),
            'orders' => Order::count(),
        ];

        return Inertia::render('Backoffice/AdminDashboard', [
            'stats' => $stats,
        ]);
    }

    public function management(): Response
    {
        $users = User::query()->orderBy('created_at', 'desc')->paginate(15, ['*'], 'users_page')->withQueryString();
        $shops = Shop::query()->with('user')->orderBy('created_at', 'desc')->paginate(15, ['*'], 'shops_page')->withQueryString();
        $products = Product::query()->with(['shop', 'category'])->orderBy('created_at', 'desc')->paginate(15, ['*'], 'products_page')->withQueryString();
        $categories = Category::query()->orderBy('created_at', 'desc')->paginate(15, ['*'], 'categories_page')->withQueryString();
        $orders = Order::query()->with('user')->orderBy('created_at', 'desc')->paginate(15, ['*'], 'orders_page')->withQueryString();

        return Inertia::render('Backoffice/Admin/Management', [
            'users' => $users,
            'shops' => $shops,
            'products' => $products,
            'categories' => $categories,
            'orders' => $orders,
            'all_users' => User::all(['id', 'name']),
            'all_shops' => Shop::all(['id', 'name']),
            'all_categories' => Category::all(['id', 'name']),
        ]);
    }
}
