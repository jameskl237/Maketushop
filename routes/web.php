<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Backoffice\Admin\AdminController;
use App\Http\Controllers\Backoffice\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Backoffice\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Backoffice\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Backoffice\Admin\ShopController as AdminShopController;
use App\Http\Controllers\Backoffice\Admin\UserController;
use App\Http\Controllers\Backoffice\SuperAdminController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\QuoteRequestController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierServiceController;
use App\Http\Controllers\UserDashboardController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminWithdrawalController;
use App\Http\Controllers\Admin\AdminVendorController;
use App\Http\Controllers\Payment\CheckoutController;
use App\Http\Controllers\Payment\PaymentCallbackController;
use App\Http\Controllers\Vendor\VendorOrderController;
use App\Http\Controllers\Vendor\VendorWalletController;
use App\Http\Controllers\Vendor\VendorSubscriptionController;
use App\Http\Controllers\Webhook\CinetPayWebhookController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// === PUBLIC PAGES ===
Route::get('/', [\App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::get('/landing', function () {
    $categories = \App\Models\Category::withCount('products')
        ->orderByDesc('products_count')
        ->limit(8)
        ->get(['id', 'name', 'slug', 'image']);
    return Inertia::render('Landing', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'dynamicCategories' => $categories,
    ]);
})->name('landing');

Route::get('/about', fn () => Inertia::render('About'))->name('about');
Route::get('/contact', fn () => Inertia::render('Contact'))->name('contact');

// === PRODUITS ===
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
Route::get('/products/{product}/buy', [ProductController::class, 'buy'])->name('products.buy');

// === PAIEMENT (nouveau flux CinetPay exclusif) ===
Route::prefix('checkout')->middleware('auth')->group(function () {
    Route::get('/product/{product}', [CheckoutController::class, 'productMethod'])->name('checkout.product.method');
    Route::post('/product/{product}', [CheckoutController::class, 'checkoutProduct'])->name('checkout.product');
    Route::get('/cart', [CheckoutController::class, 'cartMethod'])->name('checkout.cart.method');
    Route::post('/cart', [CheckoutController::class, 'checkoutCart'])->name('checkout.cart');
});

Route::get('/payments/cinetpay/callback', [PaymentCallbackController::class, 'cinetpayCallback'])->name('payments.cinetpay.callback');
Route::get('/payments/unavailable', [ProductController::class, 'paymentUnavailable'])->name('payments.unavailable');

// Anciennes routes préservées pour compatibilité (redirigent vers le nouveau flux)
Route::get('/products/{product}/payment-method', fn ($product) => redirect()->route('checkout.product.method', ['product' => $product]))->name('payments.product.method');
Route::get('/cart/payment-method', fn () => redirect()->route('checkout.cart.method'))->name('payments.cart.method');

// === LIENS DE PARTAGE ===
Route::get('/p/{product}', [ShareController::class, 'product'])->name('share.product');
Route::get('/s/{service}', [ShareController::class, 'service'])->name('share.service');

// === SERVICES ===
Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
Route::get('/services/{service}', [ServiceController::class, 'show'])->name('services.show');
Route::get('/services/{service}/buy', [ServiceController::class, 'buy'])->name('services.buy');

// === BOUTIQUES ===
Route::get('/shops', [ProductController::class, 'shops'])->name('shops.index');
Route::get('/shops/{shop}/{slug?}', [ProductController::class, 'shopShow'])->name('shops.show');

// === PANIER ===
Route::get('/cart', [ProductController::class, 'cart'])->name('cart.index');
Route::get('/cart/metadata', [ProductController::class, 'cartMetadata'])->name('cart.metadata');

// === DEVIS ===
Route::post('/services/{service}/quote', [QuoteRequestController::class, 'store'])->name('services.quote');

// === WEBHOOK CINETPAY ===
Route::match(['get', 'post'], '/webhooks/cinetpay', CinetPayWebhookController::class)
    ->name('cinetpay.webhook')
    ->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

// === RATINGS & FAVORITES ===
Route::middleware('auth')->group(function () {
    Route::post('/products/{product}/rate', [RatingController::class, 'rateProduct'])->name('ratings.product');
    Route::post('/shops/{shop}/rate', [RatingController::class, 'rateShop'])->name('ratings.shop');
    Route::post('/services/{service}/rate', [RatingController::class, 'rateService'])->name('ratings.service');
    Route::post('/favorites/toggle', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
});

// === DASHBOARD ===
Route::get('/dashboard', function () {
    return redirect()->route(request()->user()->dashboardRouteName());
})->middleware('auth')->name('dashboard');

Route::get('/user/dashboard', [UserDashboardController::class, 'index'])
    ->middleware('auth')
    ->name('user.dashboard');

// === PROFIL ===
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/phone', [ProfileController::class, 'updatePhone'])->name('profile.phone.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::patch('/orders/{order}/delivered', [UserDashboardController::class, 'markAsDelivered'])->name('orders.delivered');
});

// === BACKOFFICE ADMIN ===
Route::middleware(['auth', 'role:admin,superadmin'])->prefix('backoffice/admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('backoffice.admin.dashboard');
    Route::get('/management', [AdminController::class, 'management'])->name('backoffice.admin.management');

    // Utilisateurs
    Route::get('/users', [UserController::class, 'index'])->name('backoffice.admin.users.index');
    Route::post('/users', [UserController::class, 'store'])->name('backoffice.admin.users.store');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('backoffice.admin.users.show');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('backoffice.admin.users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('backoffice.admin.users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('backoffice.admin.users.destroy');

    // Boutiques
    Route::get('/shops', [AdminShopController::class, 'index'])->name('backoffice.admin.shops.index');
    Route::post('/shops', [AdminShopController::class, 'store'])->name('backoffice.admin.shops.store');
    Route::get('/shops/{shop}', [AdminShopController::class, 'show'])->name('backoffice.admin.shops.show');
    Route::put('/shops/{shop}', [AdminShopController::class, 'update'])->name('backoffice.admin.shops.update');
    Route::delete('/shops/{shop}', [AdminShopController::class, 'destroy'])->name('backoffice.admin.shops.destroy');

    // Produits
    Route::get('/products', [AdminProductController::class, 'index'])->name('backoffice.admin.products.index');
    Route::post('/products', [AdminProductController::class, 'store'])->name('backoffice.admin.products.store');
    Route::get('/products/{product}', [AdminProductController::class, 'show'])->name('backoffice.admin.products.show');
    Route::put('/products/{product}', [AdminProductController::class, 'update'])->name('backoffice.admin.products.update');
    Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('backoffice.admin.products.destroy');

    // Catégories
    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('backoffice.admin.categories.index');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->name('backoffice.admin.categories.store');
    Route::get('/categories/{category}', [AdminCategoryController::class, 'show'])->name('backoffice.admin.categories.show');
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->name('backoffice.admin.categories.update');
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('backoffice.admin.categories.destroy');

    // Commandes
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('backoffice.admin.orders.index');
    Route::delete('/orders/{order}', [AdminOrderController::class, 'destroy'])->name('backoffice.admin.orders.destroy');

    // === PAIEMENTS (nouveau) ===
    Route::prefix('payments')->group(function () {
        Route::get('/orders', [AdminPaymentController::class, 'orders'])->name('backoffice.admin.payments.orders');
        Route::get('/orders/{order}', [AdminPaymentController::class, 'orderShow'])->name('backoffice.admin.payments.order.show');
        Route::get('/transactions', [AdminPaymentController::class, 'transactions'])->name('backoffice.admin.payments.transactions');
        Route::get('/cinetpay', [AdminPaymentController::class, 'cinetpayTransactions'])->name('backoffice.admin.payments.cinetpay');
        Route::get('/webhooks', [AdminPaymentController::class, 'webhookLogs'])->name('backoffice.admin.payments.webhooks');
    });

    // === RETRAITS ===
    Route::prefix('withdrawals')->group(function () {
        Route::get('/', [AdminWithdrawalController::class, 'index'])->name('backoffice.admin.withdrawals.index');
        Route::post('/{withdrawalRequest}/approve', [AdminWithdrawalController::class, 'approve'])->name('backoffice.admin.withdrawals.approve');
        Route::post('/{withdrawalRequest}/reject', [AdminWithdrawalController::class, 'reject'])->name('backoffice.admin.withdrawals.reject');
        Route::post('/{withdrawalRequest}/mark-paid', [AdminWithdrawalController::class, 'markPaid'])->name('backoffice.admin.withdrawals.mark-paid');
    });

    // === FOURNISSEURS ===
    Route::prefix('vendors')->group(function () {
        Route::get('/', [AdminVendorController::class, 'index'])->name('backoffice.admin.vendors.index');
        Route::get('/{vendor}', [AdminVendorController::class, 'show'])->name('backoffice.admin.vendors.show');
    });
});

// === BACKOFFICE FOURNISSEUR ===
Route::middleware(['auth', 'role:supplier'])->prefix('backoffice/supplier')->group(function () {
    Route::get('/dashboard', [SupplierController::class, 'dashboard'])->name('backoffice.supplier.dashboard');

    // Boutiques
    Route::get('/shops', [SupplierController::class, 'shops'])->name('backoffice.supplier.shops.index');
    Route::get('/shops/{shop}', [SupplierController::class, 'showShop'])->name('backoffice.supplier.shops.show');
    Route::post('/shops', [SupplierController::class, 'storeShop'])->name('backoffice.supplier.shops.store');
    Route::put('/shops/{shop}', [SupplierController::class, 'updateShop'])->name('backoffice.supplier.shops.update');

    // Produits
    Route::get('/products', [SupplierController::class, 'products'])->name('backoffice.supplier.products.index');
    Route::post('/shops/{shop}/products', [SupplierController::class, 'storeProduct'])->name('backoffice.supplier.shops.products.store');
    Route::get('/shops/{shop}/products/{product}', [SupplierController::class, 'showProduct'])->name('backoffice.supplier.shops.products.show');
    Route::put('/shops/{shop}/products/{product}', [SupplierController::class, 'updateProduct'])->name('backoffice.supplier.shops.products.update');
    Route::delete('/shops/{shop}/products/{product}', [SupplierController::class, 'destroyProduct'])->name('backoffice.supplier.shops.products.destroy');
    Route::post('/shops/{shop}/products/{product}/medias', [SupplierController::class, 'storeProductMedias'])->name('backoffice.supplier.shops.products.medias.store');
    Route::delete('/shops/{shop}/products/{product}/medias/{media}', [SupplierController::class, 'destroyProductMedia'])->name('backoffice.supplier.shops.products.medias.destroy');

    // Services
    Route::get('/services', [SupplierServiceController::class, 'index'])->name('backoffice.supplier.services.index');
    Route::post('/services', [SupplierServiceController::class, 'store'])->name('backoffice.supplier.services.store');
    Route::put('/services/{service}', [SupplierServiceController::class, 'update'])->name('backoffice.supplier.services.update');
    Route::delete('/services/{service}', [SupplierServiceController::class, 'destroy'])->name('backoffice.supplier.services.destroy');

    // Devis
    Route::get('/quote-requests', [SupplierServiceController::class, 'quoteRequests'])->name('backoffice.supplier.quote-requests.index');
    Route::patch('/quote-requests/{quoteRequest}/handled', [SupplierServiceController::class, 'markQuoteHandled'])->name('backoffice.supplier.quote-requests.handled');

    // === COMMANDES FOURNISSEUR (nouveau workflow) ===
    Route::prefix('orders')->group(function () {
        Route::get('/', [VendorOrderController::class, 'index'])->name('backoffice.supplier.orders.index');
        Route::get('/{order}', [VendorOrderController::class, 'show'])->name('backoffice.supplier.orders.show');
        Route::post('/{order}/accept', [VendorOrderController::class, 'accept'])->name('backoffice.supplier.orders.accept');
        Route::post('/{order}/prepare', [VendorOrderController::class, 'prepare'])->name('backoffice.supplier.orders.prepare');
        Route::post('/{order}/ship', [VendorOrderController::class, 'ship'])->name('backoffice.supplier.orders.ship');
        Route::post('/{order}/deliver', [VendorOrderController::class, 'deliver'])->name('backoffice.supplier.orders.deliver');
    });

    // === PORTEFEUILLE (nouveau) ===
    Route::prefix('wallet')->group(function () {
        Route::get('/', [VendorWalletController::class, 'index'])->name('backoffice.supplier.wallet.index');
        Route::post('/withdrawal', [VendorWalletController::class, 'requestWithdrawal'])->name('backoffice.supplier.wallet.withdrawal');
    });

    // === ABONNEMENTS (nouveau) ===
    Route::prefix('subscriptions')->group(function () {
        Route::get('/', [VendorSubscriptionController::class, 'index'])->name('backoffice.supplier.subscriptions.index');
        Route::post('/{shop}/free', [VendorSubscriptionController::class, 'subscribeFree'])->name('backoffice.supplier.subscriptions.free');
        Route::post('/{shop}/standard', [VendorSubscriptionController::class, 'subscribeStandard'])->name('backoffice.supplier.subscriptions.standard');
        Route::post('/{shop}/cancel', [VendorSubscriptionController::class, 'cancel'])->name('backoffice.supplier.subscriptions.cancel');
    });
});

// === SUPERADMIN ===
Route::middleware(['auth', 'role:superadmin'])->prefix('backoffice/superadmin')->group(function () {
    Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('backoffice.superadmin.dashboard');
    Route::get('/accounting', [SuperAdminController::class, 'accounting'])->name('backoffice.superadmin.accounting');
});

// === GOOGLE AUTH ===
Route::middleware('guest')->group(function () {
    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

require __DIR__.'/auth.php';
