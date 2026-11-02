<?php

namespace App\Services\Payment;

use App\Models\CinetpayTransaction;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ShopSubscription;
use App\Services\Order\OrderService;
use App\Services\Subscription\SubscriptionManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Point unique de finalisation d'un paiement CinetPay.
 *
 * Le webhook et le retour navigateur peuvent arriver dans n'importe quel ordre
 * (voire simultanément) : les deux passent par ici. Le verrou sur la ligne
 * Payment garantit que la commande / l'abonnement n'est traité qu'une fois, et
 * que « Payment = success » implique toujours « commande payée ».
 */
class PaymentFinalizer
{
    /** Statuts CinetPay v1 définitifs en échec. */
    private const FAILED_STATUSES = ['FAILED', 'EXPIRED', 'CANCELLED', 'CANCELED', 'REFUSED', 'REJECTED'];

    public function __construct(
        private OrderService $orderService,
        private SubscriptionManager $subscriptionManager
    ) {}

    public static function isFailedStatus(?string $status): bool
    {
        return in_array(strtoupper((string) $status), self::FAILED_STATUSES, true);
    }

    /**
     * Applique le résultat d'une vérification CinetPay (/v1/payment/{id}).
     * SUCCESS → finalise ; statut d'échec définitif → échec ; sinon reste en attente.
     */
    public function apply(string $transactionId, array $verification, array $webhookPayload = []): ?Payment
    {
        $status = strtoupper((string) ($verification['status'] ?? ''));

        if (($verification['verified'] ?? false) && $status === 'SUCCESS') {
            return $this->finalizeSuccess($transactionId, $verification, $webhookPayload);
        }

        if (($verification['verified'] ?? false) && self::isFailedStatus($status)) {
            return $this->markFailed($transactionId, $status);
        }

        return Payment::where('transaction_id', $transactionId)->first();
    }

    public function finalizeSuccess(string $transactionId, array $verification, array $webhookPayload = []): ?Payment
    {
        return DB::transaction(function () use ($transactionId, $verification, $webhookPayload) {
            $payment = Payment::where('transaction_id', $transactionId)->lockForUpdate()->first();

            if (!$payment) {
                Log::warning('CinetPay.finalize_no_payment', ['transaction_id' => $transactionId]);
                return null;
            }

            if ($payment->isSuccess()) {
                return $payment; // déjà finalisé par l'autre canal
            }

            $method = $verification['payment_method'] ?? $webhookPayload['payment_method'] ?? null;

            CinetpayTransaction::where('transaction_id', $transactionId)->update(array_filter([
                'status' => 'SUCCESS',
                'paid_at' => now(),
                'payment_method' => $method,
                'cpm_trans_id' => $verification['transaction_id'] ?? $webhookPayload['transaction_id'] ?? null,
                'raw_webhook' => $webhookPayload ? json_encode($webhookPayload) : null,
            ], fn ($v) => $v !== null));

            $payment->update([
                'status' => Payment::STATUS_SUCCESS,
                'payment_method' => $method,
                'provider_data' => $verification,
                'paid_at' => now(),
            ]);

            $payable = $payment->payable;

            if ($payable instanceof Order) {
                $this->orderService->markAsPaid($payable);
            } elseif ($payable instanceof ShopSubscription) {
                $this->subscriptionManager->activatePaidSubscription($payable, $transactionId, $method ?? 'cinetpay');
            }

            Log::info('CinetPay.payment_finalized', [
                'transaction_id' => $transactionId,
                'payable' => $payment->payable_type . '#' . $payment->payable_id,
            ]);

            return $payment->fresh();
        });
    }

    public function markFailed(string $transactionId, string $status): ?Payment
    {
        return DB::transaction(function () use ($transactionId, $status) {
            $payment = Payment::where('transaction_id', $transactionId)->lockForUpdate()->first();

            if (!$payment || $payment->isSuccess()) {
                return $payment;
            }

            $payment->update(['status' => Payment::STATUS_FAILED]);
            CinetpayTransaction::where('transaction_id', $transactionId)->update(['status' => $status]);

            if ($payment->payable instanceof ShopSubscription) {
                $this->subscriptionManager->discardPendingSubscription($payment->payable);
            }

            return $payment->fresh();
        });
    }
}
