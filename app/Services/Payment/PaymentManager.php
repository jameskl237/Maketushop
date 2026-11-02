<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Models\ShopSubscription;
use App\Services\CinetPay\CinetPayService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentManager
{
    public function __construct(
        private CinetPayService $cinetpay,
        private PaymentFinalizer $finalizer
    ) {}

    public function checkoutOrder(Order $order, string $notifyUrl, string $returnUrl, array $customer = [], ?string $transactionId = null): array
    {
        $transactionId ??= $this->cinetpay->generateTransactionId();

        $payment = DB::transaction(function () use ($order, $transactionId) {
            $payment = Payment::create([
                'payable_type' => get_class($order),
                'payable_id' => $order->id,
                'provider' => 'cinetpay',
                'transaction_id' => $transactionId,
                'reference' => $order->order_number,
                'amount' => (int) round((float) $order->total_price),
                'currency' => $this->cinetpay->currency(),
                'status' => Payment::STATUS_PENDING,
            ]);

            $order->update(['payment_method' => 'cinetpay']);

            return $payment;
        });

        try {
            $result = $this->cinetpay->initializePayment([
                'merchant_transaction_id' => $transactionId,
                'amount' => $payment->amount,
                'designation' => 'Commande ' . $order->order_number . ' - MaketuShop',
                'client_email' => $customer['email'] ?? '',
                'client_first_name' => $customer['first_name'] ?? $order->customer_first_name ?? '',
                'client_last_name' => $customer['last_name'] ?? $order->customer_last_name ?? '',
                'client_phone_number' => $customer['phone'] ?? $order->phone_number ?? '',
                'notify_url' => $notifyUrl,
                'success_url' => $returnUrl,
                'failed_url' => $returnUrl,
            ]);

            // CinetPay peut arrondir le montant (multiple de 5 en FCFA) :
            // on aligne la trace locale sur ce qui sera réellement débité.
            if (isset($result['amount']) && (int) $result['amount'] !== $payment->amount) {
                $payment->update(['amount' => (int) $result['amount']]);
            }

            return [
                'success' => true,
                'payment_url' => $result['payment_url'],
                'token' => $result['payment_token'],
                'transaction_id' => $transactionId,
                'amount' => $result['amount'] ?? $payment->amount,
                'payment' => $payment->refresh(),
            ];
        } catch (\Throwable $e) {
            DB::transaction(function () use ($payment) {
                $payment->update(['status' => Payment::STATUS_FAILED]);
            });

            Log::error('PaymentManager.checkout_error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function checkoutSubscription(ShopSubscription $subscription, string $notifyUrl, string $returnUrl, array $customer = []): array
    {
        $transactionId = $this->cinetpay->generateTransactionId();
        $amount = $subscription->amount;

        $payment = DB::transaction(function () use ($subscription, $transactionId, $amount) {
            $payment = Payment::create([
                'payable_type' => get_class($subscription),
                'payable_id' => $subscription->id,
                'provider' => 'cinetpay',
                'transaction_id' => $transactionId,
                'reference' => 'SUB-' . $subscription->id . '-' . now()->format('Ymd'),
                'amount' => $amount,
                'currency' => $this->cinetpay->currency(),
                'status' => Payment::STATUS_PENDING,
            ]);

            return $payment;
        });

        try {
            $result = $this->cinetpay->initializePayment([
                'merchant_transaction_id' => $transactionId,
                'amount' => $amount,
                'designation' => 'Abonnement ' . $subscription->plan . ' - MaketuShop',
                'client_email' => $customer['email'] ?? '',
                'client_first_name' => $customer['first_name'] ?? '',
                'client_last_name' => $customer['last_name'] ?? '',
                'client_phone_number' => $customer['phone'] ?? '',
                'notify_url' => $notifyUrl,
                'success_url' => route('payments.cinetpay.callback', ['transaction_id' => $transactionId]),
                'failed_url' => route('payments.cinetpay.callback', ['transaction_id' => $transactionId]),
            ]);

            $subscription->update(['transaction_id' => $transactionId]);

            if (isset($result['amount']) && (int) $result['amount'] !== $payment->amount) {
                $payment->update(['amount' => (int) $result['amount']]);
            }

            return [
                'success' => true,
                'payment_url' => $result['payment_url'],
                'token' => $result['payment_token'],
                'transaction_id' => $transactionId,
                'amount' => $result['amount'] ?? $payment->amount,
                'payment' => $payment->refresh(),
            ];
        } catch (\Throwable $e) {
            $payment->update(['status' => Payment::STATUS_FAILED]);
            throw $e;
        }
    }

    /**
     * Retour du client depuis la page CinetPay. Le paiement peut encore être en
     * cours (validation mobile money) : on ne le marque en échec que sur un
     * statut définitif. La finalisation est partagée avec le webhook.
     */
    public function handlePaymentReturn(string $transactionId): ?Payment
    {
        $payment = Payment::where('transaction_id', $transactionId)->first();

        if (!$payment || $payment->isSuccess()) {
            return $payment;
        }

        try {
            $verification = $this->cinetpay->verifyPayment($transactionId);
            return $this->finalizer->apply($transactionId, $verification) ?? $payment->fresh();
        } catch (\Throwable $e) {
            Log::error('PaymentManager.return_verify_error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);
        }

        return $payment->fresh();
    }
}
