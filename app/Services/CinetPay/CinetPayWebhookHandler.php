<?php

namespace App\Services\CinetPay;

use App\Models\CinetpayTransaction;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ShopSubscription;
use App\Models\SubscriptionPayment;
use App\Models\VendorBalance;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CinetPayWebhookHandler
{
    public function __construct(
        private CinetPayService $cinetpay
    ) {}

    public function handle(array $payload, array $headers): array
    {
        $transactionId = $payload['cpm_trans_id'] ?? null;

        if (!$transactionId) {
            return $this->error('Transaction ID manquant dans le webhook');
        }

        $webhookLog = $this->cinetpay->logWebhook($headers, $payload, $transactionId);

        try {
            $verification = $this->cinetpay->verifyPayment($transactionId);

            if (!$this->cinetpay->isPaymentValid($verification)) {
                $this->cinetpay->markWebhookProcessed($webhookLog, 'Paiement non validé');
                return $this->success('Notification reçue, paiement non validé');
            }

            DB::transaction(function () use ($transactionId, $verification, $webhookLog) {
                $this->processValidPayment($transactionId, $verification);
                $this->cinetpay->markWebhookProcessed($webhookLog);
            });

            return $this->success('Paiement traité avec succès');
        } catch (\Throwable $e) {
            Log::error('CinetPay.webhook_handler_error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);
            $this->cinetpay->markWebhookProcessed($webhookLog, $e->getMessage());
            return $this->error($e->getMessage());
        }
    }

    private function processValidPayment(string $transactionId, array $verification): void
    {
        $cinetpayTransaction = CinetpayTransaction::where('transaction_id', $transactionId)->first();

        if (!$cinetpayTransaction) {
            Log::warning('CinetPay.webhook_orphan', ['transaction_id' => $transactionId]);
            return;
        }

        $cinetpayTransaction->update([
            'status' => 'VALIDATED',
            'raw_webhook' => request()->all(),
            'paid_at' => now(),
            'payment_method' => $verification['payment_method'],
            'cpm_trans_id' => $verification['cpm_trans_id'],
        ]);

        $payment = Payment::where('transaction_id', $cinetpayTransaction->transaction_id)->first();

        if (!$payment) {
            Log::warning('CinetPay.webhook_no_payment', [
                'transaction_id' => $cinetpayTransaction->transaction_id,
            ]);
            return;
        }

        if ($payment->isSuccess()) {
            Log::info('CinetPay.duplicate_webhook', [
                'transaction_id' => $transactionId,
                'payment_id' => $payment->id,
            ]);
            return;
        }

        $payment->update([
            'status' => Payment::STATUS_SUCCESS,
            'payment_method' => $verification['payment_method'],
            'provider_data' => $verification,
            'paid_at' => now(),
        ]);

        $payable = $payment->payable;

        if ($payable instanceof Order) {
            $this->processOrderPayment($payable, $verification);
        } elseif ($payable instanceof ShopSubscription) {
            $this->processSubscriptionPayment($payable, $verification, $payment);
        }
    }

    private function processOrderPayment(Order $order, array $verification): void
    {
        $order->update([
            'is_paid' => true,
            'payment_method' => 'cinetpay',
        ]);

        VendorBalance::initForUser($order->user_id);
    }

    private function processSubscriptionPayment(ShopSubscription $subscription, array $verification, Payment $payment): void
    {
        $subscription->update([
            'transaction_id' => $payment->transaction_id,
            'payment_method' => 'cinetpay',
        ]);

        SubscriptionPayment::where('transaction_id', $payment->transaction_id)
            ->where('status', SubscriptionPayment::STATUS_PENDING)
            ->update([
                'status' => SubscriptionPayment::STATUS_SUCCESS,
                'paid_at' => now(),
                'payment_method' => $verification['payment_method'],
                'provider_data' => $verification,
            ]);
    }

    private function success(string $message): array
    {
        return ['success' => true, 'message' => $message];
    }

    private function error(string $message): array
    {
        return ['success' => false, 'message' => $message];
    }
}
