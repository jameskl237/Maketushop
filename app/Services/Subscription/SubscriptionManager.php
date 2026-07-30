<?php

namespace App\Services\Subscription;

use App\Models\Shop;
use App\Models\ShopSubscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionManager
{
    public function getAvailablePlans(): array
    {
        return ShopSubscription::plans();
    }

    public function getShopSubscription(Shop $shop): ?ShopSubscription
    {
        return $shop->activeSubscription;
    }

    public function subscribeFree(Shop $shop, User $user): ShopSubscription
    {
        $this->ensureOwnership($shop, $user);

        return DB::transaction(function () use ($shop) {
            $this->cancelCurrent($shop);

            return ShopSubscription::create([
                'shop_id' => $shop->id,
                'plan' => ShopSubscription::PLAN_FREE,
                'status' => ShopSubscription::STATUS_ACTIVE,
                'amount' => 0,
                'payment_method' => 'free',
                'starts_at' => now(),
                'ends_at' => now()->addDays(ShopSubscription::durationFor(ShopSubscription::PLAN_FREE)),
            ]);
        });
    }

    public function initiateStandardSubscription(Shop $shop, User $user): ShopSubscription
    {
        $this->ensureOwnership($shop, $user);

        return DB::transaction(function () use ($shop) {
            $this->cancelCurrent($shop);

            $subscription = ShopSubscription::create([
                'shop_id' => $shop->id,
                'plan' => ShopSubscription::PLAN_STANDARD,
                'status' => ShopSubscription::STATUS_ACTIVE,
                'amount' => ShopSubscription::priceFor(ShopSubscription::PLAN_STANDARD),
                'payment_method' => 'cinetpay',
                'starts_at' => now(),
                'ends_at' => now()->addDays(ShopSubscription::durationFor(ShopSubscription::PLAN_STANDARD)),
            ]);

            return $subscription;
        });
    }

    public function confirmStandardPayment(ShopSubscription $subscription, string $transactionId, string $paymentMethod): void
    {
        DB::transaction(function () use ($subscription, $transactionId, $paymentMethod) {
            $subscription->update([
                'transaction_id' => $transactionId,
                'payment_method' => $paymentMethod,
                'status' => ShopSubscription::STATUS_ACTIVE,
            ]);

            SubscriptionPayment::create([
                'shop_subscription_id' => $subscription->id,
                'plan' => ShopSubscription::PLAN_STANDARD,
                'amount' => $subscription->amount,
                'transaction_id' => $transactionId,
                'payment_method' => $paymentMethod,
                'status' => SubscriptionPayment::STATUS_SUCCESS,
                'paid_at' => now(),
                'period_start' => $subscription->starts_at,
                'period_end' => $subscription->ends_at,
            ]);
        });
    }

    public function cancel(Shop $shop, User $user): void
    {
        $this->ensureOwnership($shop, $user);
        $this->cancelCurrent($shop);
    }

    public function isStandard(Shop $shop): bool
    {
        $sub = $shop->activeSubscription;
        return $sub && $sub->isStandard() && $sub->isActive();
    }

    public function getStandardShops()
    {
        return Shop::whereHas('activeSubscription', function ($q) {
            $q->where('plan', ShopSubscription::PLAN_STANDARD)
              ->where('status', ShopSubscription::STATUS_ACTIVE);
        });
    }

    public function expireOldSubscriptions(): int
    {
        return ShopSubscription::where('status', ShopSubscription::STATUS_ACTIVE)
            ->where('ends_at', '<', now())
            ->update(['status' => ShopSubscription::STATUS_EXPIRED]);
    }

    private function ensureOwnership(Shop $shop, User $user): void
    {
        if ($shop->user_id !== $user->id) {
            throw new \RuntimeException('Cette boutique ne vous appartient pas');
        }
    }

    private function cancelCurrent(Shop $shop): void
    {
        $current = $shop->activeSubscription;
        if ($current) {
            $current->update([
                'status' => ShopSubscription::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);
        }
    }
}
