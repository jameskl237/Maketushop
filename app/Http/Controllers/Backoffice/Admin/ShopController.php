<?php

namespace App\Http\Controllers\Backoffice\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopController extends Controller
{
    public function index(Request $request): Response
    {
        $perPage = (int) $request->get('per_page', 15);

        $shops = Shop::query()->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();

        return Inertia::render('Backoffice/Admin/Shops/Index', [
            'shops' => $shops,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $shop = Shop::create($data);

        return redirect()->route('backoffice.admin.shops.index')->with('success', 'Boutique créée.');
    }

    public function show(Shop $shop)
    {
        return Inertia::render('Backoffice/Admin/Shops/Show', [
            'shop' => $shop,
        ]);
    }

    public function update(Request $request, Shop $shop)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $shop->update($data);

        return redirect()->route('backoffice.admin.shops.index')->with('success', 'Boutique mise à jour.');
    }

    public function destroy(Shop $shop)
    {
        $shop->delete();

        return redirect()->route('backoffice.admin.shops.index')->with('success', 'Boutique supprimée.');
    }
}
