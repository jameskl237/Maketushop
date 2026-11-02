<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Service;
use App\Models\Shop;
use App\Models\ShopSubscription;
use App\Services\CinetPay\CinetPayService;
use App\Services\Order\OrderService;
use App\Services\Payment\PaymentManager;
use App\Services\Subscription\SubscriptionManager;
use App\Services\Wallet\VendorWalletService;
use App\Services\Wallet\WithdrawalService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CinetPayService::class);
        $this->app->singleton(OrderService::class);
        $this->app->singleton(PaymentManager::class);
        $this->app->singleton(VendorWalletService::class);
        $this->app->singleton(WithdrawalService::class);
        $this->app->singleton(SubscriptionManager::class);
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Derrière un proxy TLS (Cloudflare, nginx…), PHP voit du http : sans ça,
        // route() génère des notify_url / success_url en http pour CinetPay.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Relation::morphMap([
            'order' => Order::class,
            'subscription' => ShopSubscription::class,
            'product' => Product::class,
            'service' => Service::class,
            'shop' => Shop::class,
        ]);
    }
}
