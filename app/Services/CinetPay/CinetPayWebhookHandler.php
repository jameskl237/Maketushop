<?php

namespace App\Services\CinetPay;

use App\Services\Payment\PaymentFinalizer;
use Illuminate\Support\Facades\Log;

class CinetPayWebhookHandler
{
    public function __construct(
        private CinetPayService $cinetpay,
        private PaymentFinalizer $finalizer
    ) {}

    public function handle(array $payload, array $headers, ?string $token = null): array
    {
        $transactionId = $payload['merchant_transaction_id'] ?? $payload['transaction_id'] ?? null;

        if (!$transactionId) {
            return $this->error('merchant_transaction_id manquant dans le webhook');
        }

        $webhookLog = $this->cinetpay->logWebhook($headers, $payload, $transactionId);

        try {
            $verified = $this->cinetpay->verifyWebhook($payload);

            if (!$verified['valid']) {
                $this->cinetpay->markWebhookProcessed($webhookLog, $verified['reason'] ?? 'Notification invalide');
                return $this->error($verified['reason'] ?? 'Notification invalide');
            }

            $transactionId = $verified['merchant_transaction_id'];

            // La notification ne fait qu'annoncer un changement d'état :
            // seul l'appel à l'API de vérification fait foi.
            $verification = $this->cinetpay->verifyPayment($transactionId);
            $payment = $this->finalizer->apply($transactionId, $verification, $payload);

            $this->cinetpay->markWebhookProcessed($webhookLog);

            return $payment?->isSuccess()
                ? $this->success('Paiement traité avec succès')
                : $this->success('Notification reçue, statut : ' . ($verification['status'] ?? 'inconnu'));
        } catch (\Throwable $e) {
            Log::error('CinetPay.webhook_handler_error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);
            $this->cinetpay->markWebhookProcessed($webhookLog, $e->getMessage());
            return $this->error($e->getMessage());
        }
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
