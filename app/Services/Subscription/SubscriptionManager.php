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

    /**
     * Crée l'abonnement Standard en attente de paiement. L'abonnement actuel
     * reste actif tant que CinetPay n'a pas confirmé le paiement.
     */
    public function initiateStandardSubscription(Shop $shop, User $user): ShopSubscription
    {
        $this->ensureOwnership($shop, $user);

        return DB::transaction(function () use ($shop) {
            // Un seul Standard en attente par boutique.
            ShopSubscription::where('shop_id', $shop->id)
                ->where('status', ShopSubscription::STATUS_PENDING)
                ->update(['status' => ShopSubscription::STATUS_CANCELLED, 'cancelled_at' => now()]);

            return ShopSubscription::create([
                'shop_id' => $shop->id,
                'plan' => ShopSubscription::PLAN_STANDARD,
                'status' => ShopSubscription::STATUS_PENDING,
                'amount' => ShopSubscription::priceFor(ShopSubscription::PLAN_STANDARD),
                'payment_method' => 'cinetpay',
            ]);
        });
    }

    /**
     * Active un abonnement après confirmation du paiement par CinetPay.
     * Idempotent : appelé depuis PaymentFinalizer sous verrou.
     */
    public function activatePaidSubscription(ShopSubscription $subscription, string $transactionId, string $paymentMethod): void
    {
        DB::transaction(function () use ($subscription, $transactionId, $paymentMethod) {
            $subscription->refresh();

            if ($subscription->status === ShopSubscription::STATUS_ACTIVE) {
                return;
            }

            ShopSubscription::where('shop_id', $subscription->shop_id)
                ->where('id', '!=', $subscription->id)
                ->where('status', ShopSubscription::STATUS_ACTIVE)
                ->update(['status' => ShopSubscription::STATUS_CANCELLED, 'cancelled_at' => now()]);

            $startsAt = now();
            $endsAt = now()->addDays(ShopSubscription::durationFor($subscription->plan));

            $subscription->update([
                'transaction_id' => $transactionId,
                'payment_method' => $paymentMethod,
                'status' => ShopSubscription::STATUS_ACTIVE,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);

            SubscriptionPayment::create([
                'shop_subscription_id' => $subscription->id,
                'plan' => $subscription->plan,
                'amount' => $subscription->amount,
                'transaction_id' => $transactionId,
                'payment_method' => $paymentMethod,
                'status' => SubscriptionPayment::STATUS_SUCCESS,
                'paid_at' => now(),
                'period_start' => $startsAt,
                'period_end' => $endsAt,
            ]);
        });
    }

    public function discardPendingSubscription(ShopSubscription $subscription): void
    {
        if ($subscription->status === ShopSubscription::STATUS_PENDING) {
            $subscription->update([
                'status' => ShopSubscription::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);
        }
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
