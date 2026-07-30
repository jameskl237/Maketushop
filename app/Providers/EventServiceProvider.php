<?php

namespace App\Providers;

use App\Events\OrderDelivered;
use App\Events\OrderPaid;
use App\Events\SubscriptionUpgraded;
use App\Events\WithdrawalRequested;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderPaid::class => [],
        OrderDelivered::class => [],
        WithdrawalRequested::class => [],
        SubscriptionUpgraded::class => [],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return true;
    }
}
