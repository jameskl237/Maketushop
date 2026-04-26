<?php

namespace App\Http\Controllers\Backoffice\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('backoffice.admin.management', ['tab' => 'categories']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $category = Category::create($data);

        return redirect()->route('backoffice.admin.management', ['tab' => 'categories'])->with('success', 'Catégorie créée.');
    }

    public function show(Category $category)
    {
        return Inertia::render('Backoffice/Admin/Categories/Show', [
            'category' => $category,
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $category->update($data);

        return redirect()->route('backoffice.admin.management', ['tab' => 'categories'])->with('success', 'Catégorie mise à jour.');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->route('backoffice.admin.management', ['tab' => 'categories'])->with('success', 'Catégorie supprimée.');
    }
}
