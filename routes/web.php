<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\Backoffice\Admin\UserController;
use App\Http\Controllers\Backoffice\Admin\AdminController;
use App\Http\Controllers\Backoffice\Admin\ShopController as AdminShopController;
use App\Http\Controllers\Backoffice\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Backoffice\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Backoffice\Admin\OrderController as AdminOrderController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/about', function () {
    return Inertia::render('About');
})->name('about');

Route::get('/contact', function () {
    return Inertia::render('Contact');
})->name('contact');

Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
Route::get('/products/{product}/buy', [ProductController::class, 'buy'])->name('products.buy');
Route::get('/payments/unavailable', [ProductController::class, 'paymentUnavailable'])->name('payments.unavailable');
Route::get('/shops', [ProductController::class, 'shops'])->name('shops.index');
Route::get('/shops/{shop}/{slug?}', [ProductController::class, 'shopShow'])->name('shops.show');
Route::get('/cart', [ProductController::class, 'cart'])->name('cart.index');
Route::get('/cart/metadata', [ProductController::class, 'cartMetadata'])->name('cart.metadata');

Route::get('/dashboard', function () {
    $user = request()->user();

    return redirect()->route($user->dashboardRouteName());
})->middleware('auth')->name('dashboard');

Route::get('/user/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware('auth')->name('user.dashboard');

Route::middleware(['auth', 'role:admin'])->prefix('backoffice/admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('backoffice.admin.dashboard');
    
    // Admin: gestion des utilisateurs (CRUD)
    Route::get('/users', [UserController::class, 'index'])->name('backoffice.admin.users.index');
    Route::post('/users', [UserController::class, 'store'])->name('backoffice.admin.users.store');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('backoffice.admin.users.show');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('backoffice.admin.users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('backoffice.admin.users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('backoffice.admin.users.destroy');
    
    // Management single page
    Route::get('/management', [AdminController::class, 'management'])->name('backoffice.admin.management');

    // Admin: gestion des boutiques / produits / categories (index placeholders)
    Route::get('/shops', [AdminShopController::class, 'index'])->name('backoffice.admin.shops.index');
    Route::post('/shops', [AdminShopController::class, 'store'])->name('backoffice.admin.shops.store');
    Route::get('/shops/{shop}', [AdminShopController::class, 'show'])->name('backoffice.admin.shops.show');
    Route::put('/shops/{shop}', [AdminShopController::class, 'update'])->name('backoffice.admin.shops.update');
    Route::delete('/shops/{shop}', [AdminShopController::class, 'destroy'])->name('backoffice.admin.shops.destroy');
    Route::get('/products', [AdminProductController::class, 'index'])->name('backoffice.admin.products.index');
    Route::post('/products', [AdminProductController::class, 'store'])->name('backoffice.admin.products.store');
    Route::get('/products/{product}', [AdminProductController::class, 'show'])->name('backoffice.admin.products.show');
    Route::put('/products/{product}', [AdminProductController::class, 'update'])->name('backoffice.admin.products.update');
    Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('backoffice.admin.products.destroy');
    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('backoffice.admin.categories.index');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->name('backoffice.admin.categories.store');
    Route::get('/categories/{category}', [AdminCategoryController::class, 'show'])->name('backoffice.admin.categories.show');
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->name('backoffice.admin.categories.update');
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('backoffice.admin.categories.destroy');

    // Admin: gestion des commandes
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('backoffice.admin.orders.index');
    Route::delete('/orders/{order}', [AdminOrderController::class, 'destroy'])->name('backoffice.admin.orders.destroy');
});

Route::middleware(['auth', 'role:supplier'])->prefix('backoffice/supplier')->group(function () {
    Route::get('/dashboard', [SupplierController::class, 'dashboard'])->name('backoffice.supplier.dashboard');
    Route::get('/shops', [SupplierController::class, 'shops'])->name('backoffice.supplier.shops.index');
    Route::get('/products', [SupplierController::class, 'products'])->name('backoffice.supplier.products.index');
    Route::get('/orders', [SupplierController::class, 'orders'])->name('backoffice.supplier.orders.index');
    Route::get('/categories', [SupplierController::class, 'categories'])->name('backoffice.supplier.categories.index');
    Route::post('/shops', [SupplierController::class, 'storeShop'])->name('backoffice.supplier.shops.store');
    Route::put('/shops/{shop}', [SupplierController::class, 'updateShop'])->name('backoffice.supplier.shops.update');
    Route::get('/shops/{shop}', [SupplierController::class, 'showShop'])->name('backoffice.supplier.shops.show');
    Route::post('/shops/{shop}/products', [SupplierController::class, 'storeProduct'])->name('backoffice.supplier.shops.products.store');
    Route::get('/shops/{shop}/products/{product}', [SupplierController::class, 'showProduct'])->name('backoffice.supplier.shops.products.show');
    Route::put('/shops/{shop}/products/{product}', [SupplierController::class, 'updateProduct'])->name('backoffice.supplier.shops.products.update');
    Route::delete('/shops/{shop}/products/{product}', [SupplierController::class, 'destroyProduct'])->name('backoffice.supplier.shops.products.destroy');
    Route::post('/shops/{shop}/products/{product}/medias', [SupplierController::class, 'storeProductMedias'])->name('backoffice.supplier.shops.products.medias.store');
    Route::delete('/shops/{shop}/products/{product}/medias/{media}', [SupplierController::class, 'destroyProductMedia'])->name('backoffice.supplier.shops.products.medias.destroy');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('guest')->group(function () {
    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

require __DIR__.'/auth.php';
