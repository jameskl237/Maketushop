<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Services\Payment\PaymentManager;
use App\Services\Subscription\SubscriptionManager;
use App\Support\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class VendorSubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionManager $subscriptionManager,
        private PaymentManager $paymentManager
    ) {
    }

    public function index()
    {
        $vendor = Auth::user();
        $shops = $vendor->shops()->with('activeSubscription')->get();

        return Inertia::render('Backoffice/Vendor/Subscriptions', [
            'plans' => $this->subscriptionManager->getAvailablePlans(),
            'shops' => $shops,
        ]);
    }

    public function subscribeFree(Shop $shop)
    {
        $this->authorizeShop($shop);

        $this->subscriptionManager->subscribeFree($shop, Auth::user());

        return back()->with('success', 'Abonnement gratuit activé.');
    }

    public function subscribeStandard(Shop $shop)
    {
        $this->authorizeShop($shop);

        $customer = Auth::user();
        // Le paiement mobile money pousse la demande de validation sur ce numéro :
        // pas de numéro fictif, on exige un vrai mobile.
        $phone = PhoneNumber::toE164($customer->phone ?: $shop->phone);

        if (!PhoneNumber::isValidMobile($phone)) {
            return back()->with('error', 'Renseignez un numéro mobile money valide dans votre profil avant de souscrire.');
        }

        $nameParts = preg_split('/\s+/', trim((string) $customer->name), 2);

        try {
            $subscription = $this->subscriptionManager->initiateStandardSubscription($shop, $customer);

            $result = $this->paymentManager->checkoutSubscription(
                $subscription,
                notifyUrl: route('cinetpay.webhook'),
                returnUrl: route('payments.cinetpay.callback', ['transaction_id' => 'SUB-' . $subscription->id]),
                customer: [
                    'email' => $customer->email,
                    'phone' => $phone,
                    'first_name' => $nameParts[0] ?: 'Client',
                    'last_name' => $nameParts[1] ?? ($nameParts[0] ?: 'MaketuShop'),
                ]
            );

            return Inertia::location($result['payment_url']);
        } catch (\Throwable $e) {
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function cancel(Shop $shop)
    {
        $this->authorizeShop($shop);
        $this->subscriptionManager->cancel($shop, Auth::user());

        return back()->with('success', 'Abonnement résilié. Passage en mode gratuit.');
    }

    private function authorizeShop(Shop $shop): void
    {
        if ($shop->user_id !== Auth::id()) {
            abort(403, 'Cette boutique ne vous appartient pas.');
        }
    }
}
