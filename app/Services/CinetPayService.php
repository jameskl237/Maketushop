<?php

namespace App\Services;

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
        return 'TXN-' . now()->format('YmdHis') . '-' . Str::random(8);
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

        $response = Http::timeout(15)->post("{$this->baseUrl}/payment", $payload);

        if ($response->failed()) {
            Log::error('CinetPay init failed', ['response' => $response->body()]);
            throw new \RuntimeException('CinetPay : ' . ($response->json('message') ?? 'Erreur de communication'));
        }

        $result = $response->json();

        if (($result['code'] ?? '') !== '00') {
            Log::error('CinetPay init error', $result);
            throw new \RuntimeException('CinetPay : ' . ($result['message'] ?? $result['description'] ?? 'Erreur inconnue'));
        }

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
            Log::error('CinetPay verify failed', ['response' => $response->body()]);
            throw new \RuntimeException('CinetPay : impossible de vérifier la transaction');
        }

        $result = $response->json();

        if (($result['code'] ?? '') !== '00') {
            return ['verified' => false, 'error' => $result['message'] ?? 'Erreur de vérification'];
        }

        $data = $result['data'];

        return [
            'verified' => true,
            'status' => $data['cpm_trans_status'] ?? null,
            'amount' => $data['cpm_amount'] ?? null,
            'currency' => $data['cpm_currency'] ?? null,
            'payment_method' => $data['payment_method'] ?? null,
            'payment_date' => $data['cpm_trans_date'] ?? null,
            'customer_name' => $data['cpm_customer_name'] ?? null,
            'customer_email' => $data['cpm_customer_email'] ?? null,
            'reference' => $data['cpm_trans_id'] ?? null,
        ];
    }

    public function isPaymentValid(array $verification): bool
    {
        return ($verification['verified'] ?? false) && ($verification['status'] ?? '') === 'VALIDATED';
    }
}
