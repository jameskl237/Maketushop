<?php

namespace App\Services\CinetPay;

use App\Models\CinetpayTransaction;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CinetPayService
{
    private string $apiKey;
    private string $siteId;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.cinetpay.api_key');
        $this->siteId = config('services.cinetpay.site_id');
        $this->baseUrl = config('services.cinetpay.base_url', 'https://api-checkout.cinetpay.com/v2');
    }

    public function isConfigured(): bool
    {
        return filled($this->apiKey) && filled($this->siteId);
    }

    public function generateTransactionId(): string
    {
        return 'TXN-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(8));
    }

    public function initializePayment(array $params): array
    {
        $payload = array_merge([
            'apikey' => $this->apiKey,
            'site_id' => $this->siteId,
            'currency' => 'XOF',
            'channels' => 'ALL',
            'lang' => 'FR',
        ], $params);

        $this->validatePayload($payload);

        $response = Http::timeout(15)->post("{$this->baseUrl}/payment", $payload);

        $this->logTransaction($payload['transaction_id'], $payload, $response->body());

        if ($response->failed()) {
            Log::error('CinetPay.init_failed', [
                'transaction_id' => $payload['transaction_id'],
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new CinetPayException(
                $response->json('message') ?? 'Erreur de communication avec CinetPay',
                $response->json('code') ?? 'NETWORK_ERROR'
            );
        }

        $result = $response->json();

        if (($result['code'] ?? '') !== '00') {
            Log::error('CinetPay.init_error', [
                'transaction_id' => $payload['transaction_id'],
                'code' => $result['code'] ?? null,
                'message' => $result['message'] ?? null,
            ]);
            throw new CinetPayException(
                $result['message'] ?? $result['description'] ?? 'Erreur inconnue',
                $result['code'] ?? 'UNKNOWN'
            );
        }

        $this->updateTransactionStatus($payload['transaction_id'], 'CREATED', $response->json());

        return [
            'success' => true,
            'token' => $result['data']['token'],
            'payment_url' => $result['data']['payment_url'],
            'transaction_id' => $payload['transaction_id'],
        ];
    }

    public function verifyPayment(string $transactionId): array
    {
        $response = Http::timeout(10)->post("{$this->baseUrl}/payment/check", [
            'apikey' => $this->apiKey,
            'site_id' => $this->siteId,
            'transaction_id' => $transactionId,
        ]);

        if ($response->failed()) {
            Log::error('CinetPay.verify_failed', [
                'transaction_id' => $transactionId,
                'status' => $response->status(),
            ]);
            throw new CinetPayException('Impossible de vérifier la transaction', 'VERIFY_FAILED');
        }

        $result = $response->json();

        if (($result['code'] ?? '') !== '00') {
            return [
                'verified' => false,
                'error' => $result['message'] ?? 'Erreur de vérification',
                'code' => $result['code'] ?? null,
            ];
        }

        $data = $result['data'];
        $status = $data['cpm_trans_status'] ?? null;

        $this->updateTransactionStatus($transactionId, $status, $result);

        return [
            'verified' => true,
            'status' => $status,
            'amount' => (int) ($data['cpm_amount'] ?? 0),
            'currency' => $data['cpm_currency'] ?? 'XOF',
            'payment_method' => $data['payment_method'] ?? null,
            'payment_date' => $data['cpm_trans_date'] ?? null,
            'customer_name' => $data['cpm_customer_name'] ?? null,
            'customer_email' => $data['cpm_customer_email'] ?? null,
            'cpm_trans_id' => $data['cpm_trans_id'] ?? null,
            'phone' => $data['cpm_phone_prefill'] ?? null,
        ];
    }

    public function isPaymentValid(array $verification): bool
    {
        return ($verification['verified'] ?? false)
            && ($verification['status'] ?? '') === 'VALIDATED';
    }

    public function logWebhook(array $headers, array $payload, ?string $transactionId = null): WebhookLog
    {
        return WebhookLog::create([
            'provider' => 'cinetpay',
            'event' => $payload['cpm_trans_status'] ?? 'notification',
            'transaction_id' => $transactionId ?? $payload['cpm_trans_id'] ?? null,
            'status' => 'received',
            'headers' => $headers,
            'payload' => $payload,
            'ip_address' => request()->ip(),
        ]);
    }

    public function markWebhookProcessed(WebhookLog $log, ?string $error = null): void
    {
        $log->update([
            'status' => $error ? 'error' : 'processed',
            'error' => $error ? ['message' => $error] : null,
        ]);
    }

    public function getChannels(): array
    {
        return Http::timeout(10)->get("{$this->baseUrl}/channels", [
            'apikey' => $this->apiKey,
            'site_id' => $this->siteId,
        ])->json() ?? [];
    }

    private function validatePayload(array $payload): void
    {
        $required = ['transaction_id', 'amount', 'description', 'notify_url', 'return_url', 'customer'];
        foreach ($required as $field) {
            if (empty($payload[$field])) {
                throw new CinetPayException("Le champ '{$field}' est requis", 'VALIDATION_ERROR');
            }
        }

        if ($payload['amount'] <= 0) {
            throw new CinetPayException('Le montant doit être supérieur à 0', 'VALIDATION_ERROR');
        }

        if (!is_string($payload['transaction_id']) || strlen($payload['transaction_id']) > 100) {
            throw new CinetPayException('Transaction ID invalide', 'VALIDATION_ERROR');
        }
    }

    private function logTransaction(string $transactionId, array $request, string $response): void
    {
        try {
            CinetpayTransaction::updateOrCreate(
                ['transaction_id' => $transactionId],
                [
                    'site_id' => $this->siteId,
                    'amount' => $request['amount'] ?? 0,
                    'currency' => $request['currency'] ?? 'XOF',
                    'status' => 'INITIATED',
                    'description' => $request['description'] ?? null,
                    'customer_name' => $request['customer']['name'] ?? null,
                    'customer_email' => $request['customer']['email'] ?? null,
                    'customer_phone' => $request['customer']['phone_number'] ?? null,
                    'raw_request' => $request,
                    'raw_response' => json_decode($response, true) ?? [],
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('CinetPay.log_transaction_error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function updateTransactionStatus(string $transactionId, ?string $status, array $data): void
    {
        try {
            $update = ['status' => $status ?? 'UNKNOWN'];
            if ($status === 'VALIDATED') {
                $update['paid_at'] = now();
                $update['cpm_trans_id'] = $data['data']['cpm_trans_id'] ?? null;
                $update['payment_method'] = $data['data']['payment_method'] ?? null;
            }
            CinetpayTransaction::where('transaction_id', $transactionId)->update($update);
        } catch (\Throwable $e) {
            Log::warning('CinetPay.update_status_error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
