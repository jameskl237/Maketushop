<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use App\Models\ShopSubscription;
use App\Services\CinetPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        $shops = $user->shops()->with('activeSubscription')->get();

        return Inertia::render('Subscriptions/Index', [
            'plans' => ShopSubscription::plans(),
            'shops' => $shops,
        ]);
    }

    public function subscribe(Request $request, Shop $shop, CinetPayService $cinetpay)
    {
        $validated = $request->validate([
            'plan' => ['required', 'string', 'in:free,standard'],
        ]);

        $plan = $validated['plan'];

        if ($shop->user_id !== Auth::id()) {
            return back()->with('error', 'Cette boutique ne vous appartient pas.');
        }

        if ($shop->activeSubscription && $plan === $shop->activeSubscription->plan && $shop->activeSubscription->isActive()) {
            return back()->with('info', 'Vous avez déjà un abonnement actif à ce plan.');
        }

        if ($plan === ShopSubscription::PLAN_FREE) {
            return $this->activateFreeSubscription($shop);
        }

        return $this->initiatePaidSubscription($shop, $cinetpay);
    }

    public function payment(Request $request, Shop $shop, CinetPayService $cinetpay)
    {
        if ($shop->user_id !== Auth::id()) {
            return back()->with('error', 'Cette boutique ne vous appartient pas.');
        }

        $customer = Auth::user();

        $transactionId = $cinetpay->generateTransactionId();

        try {
            $payment = $cinetpay->initializePayment([
                'transaction_id' => $transactionId,
                'amount' => ShopSubscription::priceFor(ShopSubscription::PLAN_STANDARD),
                'description' => "Abonnement Standard - {$shop->name} - MaketuShop",
                'notify_url' => route('cinetpay.webhook'),
                'return_url' => route('subscriptions.callback', ['shop' => $shop, 'transaction_id' => $transactionId]),
                'customer' => [
                    'name' => $customer->name ?? '',
                    'surname' => '',
                    'email' => $customer->email ?? '',
                    'phone_number' => $customer->phone ?? '+2250000000000',
                    'country' => 'CI',
                ],
                'metadata' => [
                    'type' => 'shop_subscription',
                    'shop_id' => $shop->id,
                    'plan' => ShopSubscription::PLAN_STANDARD,
                ],
            ]);

            DB::transaction(function () use ($shop, $transactionId, $payment) {
                $shop->activeSubscription?->update(['status' => ShopSubscription::STATUS_CANCELLED, 'cancelled_at' => now()]);

                ShopSubscription::create([
                    'shop_id' => $shop->id,
                    'plan' => ShopSubscription::PLAN_STANDARD,
                    'status' => ShopSubscription::STATUS_ACTIVE,
                    'amount' => ShopSubscription::priceFor(ShopSubscription::PLAN_STANDARD),
                    'transaction_id' => $transactionId,
                    'payment_method' => 'cinetpay',
                    'starts_at' => now(),
                    'ends_at' => now()->addDays(ShopSubscription::durationFor(ShopSubscription::PLAN_STANDARD)),
                ]);
            });

            return Inertia::location($payment['payment_url']);
        } catch (\Throwable $e) {
            return back()->with('error', 'Erreur de paiement : ' . $e->getMessage());
        }
    }

    public function callback(Request $request, Shop $shop, CinetPayService $cinetpay)
    {
        $transactionId = $request->query('transaction_id');

        if (! $transactionId) {
            return redirect()->route('subscriptions.index')->with('error', 'Référence de paiement manquante.');
        }

        try {
            $verification = $cinetpay->verifyPayment($transactionId);

            if ($cinetpay->isPaymentValid($verification)) {
                $subscription = ShopSubscription::where('transaction_id', $transactionId)->first();

                if ($subscription && ! $subscription->isActive()) {
                    $subscription->update([
                        'status' => ShopSubscription::STATUS_ACTIVE,
                        'starts_at' => now(),
                        'ends_at' => now()->addDays(ShopSubscription::durationFor(ShopSubscription::PLAN_STANDARD)),
                    ]);
                }

                return redirect()->route('subscriptions.index')->with('success', 'Abonnement Standard activé avec succès !');
            }

            ShopSubscription::where('transaction_id', $transactionId)
                ->where('status', ShopSubscription::STATUS_ACTIVE)
                ->update(['status' => ShopSubscription::STATUS_CANCELLED, 'cancelled_at' => now()]);

            return redirect()->route('subscriptions.index')->with('error', 'Le paiement n\'a pas été confirmé. Veuillez réessayer.');
        } catch (\Throwable $e) {
            return redirect()->route('subscriptions.index')->with('error', 'Erreur de vérification : ' . $e->getMessage());
        }
    }

    public function cancel(Shop $shop)
    {
        if ($shop->user_id !== Auth::id()) {
            return back()->with('error', 'Cette boutique ne vous appartient pas.');
        }

        $shop->activeSubscription?->update([
            'status' => ShopSubscription::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        return back()->with('success', 'Abonnement annulé. Vous passez en mode gratuit.');
    }

    private function activateFreeSubscription(Shop $shop)
    {
        DB::transaction(function () use ($shop) {
            $shop->activeSubscription?->update([
                'status' => ShopSubscription::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);

            ShopSubscription::create([
                'shop_id' => $shop->id,
                'plan' => ShopSubscription::PLAN_FREE,
                'status' => ShopSubscription::STATUS_ACTIVE,
                'amount' => 0,
                'transaction_id' => null,
                'payment_method' => 'free',
                'starts_at' => now(),
                'ends_at' => now()->addDays(ShopSubscription::durationFor(ShopSubscription::PLAN_FREE)),
            ]);
        });

        return back()->with('success', 'Abonnement Gratuit activé !');
    }
}
