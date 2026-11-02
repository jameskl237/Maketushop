<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ShopSubscription;
use App\Services\Payment\PaymentManager;
use Illuminate\Http\Request;

class PaymentCallbackController extends Controller
{
    public function __construct(
        private PaymentManager $paymentManager
    ) {}

    public function cinetpayCallback(Request $request)
    {
        $transactionId = $request->query('transaction_id');

        if (!$transactionId) {
            return redirect()->route('home')->with('error', 'Référence de paiement manquante.');
        }

        $payment = $this->paymentManager->handlePaymentReturn($transactionId);

        if (!$payment) {
            return redirect()->route('home')->with('error', 'Transaction introuvable.');
        }

        $payable = $payment->payable;

        if ($payable instanceof Order) {
            if ($payment->isSuccess()) {
                return redirect()->route('user.dashboard')
                    ->with('success', 'Paiement réussi ! Votre commande est en cours de traitement.');
            }
            if ($payment->isPending()) {
                return redirect()->route('user.dashboard')
                    ->with('message', 'Paiement en cours de confirmation. Validez la demande sur votre téléphone ; votre commande sera mise à jour automatiquement.');
            }
            return redirect()->route('user.dashboard')
                ->with('error', 'Le paiement n\'a pas abouti. Veuillez réessayer.');
        }

        if ($payable instanceof ShopSubscription) {
            if ($payment->isSuccess()) {
                return redirect()->route('subscriptions.index')
                    ->with('success', 'Abonnement Standard activé avec succès !');
            }
            if ($payment->isPending()) {
                return redirect()->route('subscriptions.index')
                    ->with('message', 'Paiement en cours de confirmation. L\'abonnement sera activé dès réception de la confirmation CinetPay.');
            }
            return redirect()->route('subscriptions.index')
                ->with('error', 'Le paiement de l\'abonnement a échoué.');
        }

        return redirect()->route('home');
    }
}
