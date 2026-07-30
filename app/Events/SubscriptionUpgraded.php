<?php

namespace App\Events;

use App\Models\ShopSubscription;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionUpgraded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ShopSubscription $subscription
    ) {}
}
