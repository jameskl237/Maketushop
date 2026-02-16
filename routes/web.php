<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SupplierController;
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
Route::get('/shops/{shop}', [ProductController::class, 'shopShow'])->name('shops.show');
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
    Route::get('/dashboard', function () {
        return Inertia::render('Backoffice/AdminDashboard');
    })->name('backoffice.admin.dashboard');
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

require __DIR__.'/auth.php';
