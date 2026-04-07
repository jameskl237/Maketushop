<?php

namespace App\Http\Controllers\Backoffice\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $perPage = (int) $request->get('per_page', 15);

        $products = Product::query()->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();

        return Inertia::render('Backoffice/Admin/Products/Index', [
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'promotion_price' => 'nullable|numeric',
            'in_stock' => 'boolean',
            'quantity' => 'nullable|integer',
            'category_id' => 'nullable|exists:categories,id',
            'shop_id' => 'nullable|exists:shops,id',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $product = Product::create($data);

        return redirect()->route('backoffice.admin.products.index')->with('success', 'Produit créé.');
    }

    public function show(Product $product)
    {
        return Inertia::render('Backoffice/Admin/Products/Show', [
            'product' => $product,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'promotion_price' => 'nullable|numeric',
            'in_stock' => 'boolean',
            'quantity' => 'nullable|integer',
            'category_id' => 'nullable|exists:categories,id',
            'shop_id' => 'nullable|exists:shops,id',
        ]);

        $product->update($data);

        return redirect()->route('backoffice.admin.products.index')->with('success', 'Produit mis à jour.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('backoffice.admin.products.index')->with('success', 'Produit supprimé.');
    }
}
