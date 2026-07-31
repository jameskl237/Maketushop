<?php

namespace App\Services\CinetPay;

use App\Models\CinetpayTransaction;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CinetPayService
{
    private string $apiKey;
    private string $apiPassword;
    private string $baseUrl;
    private string $country;

    private const SUCCESS_CODES = [200, 100];
    private const TOKEN_ERROR_CODES = [1002, 1003];

    public function __construct()
    {
        $this->apiKey = (string) config('services.cinetpay.api_key', '');
        $this->apiPassword = (string) config('services.cinetpay.api_password', '');
        $this->baseUrl = rtrim((string) config('services.cinetpay.base_url', 'https://api.cinetpay.co'), '/');
        $this->country = strtoupper((string) config('services.cinetpay.country', 'CI'));
    }

    public function isConfigured(): bool
    {
        return filled($this->apiKey) && filled($this->apiPassword);
    }

    public function generateTransactionId(): string
    {
        return 'TXN-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(8));
    }

    public function initializePayment(array $params): array
    {
        $payload = array_merge([
            'currency' => 'XOF',
            'lang' => 'fr',
            'channel' => 'PUSH',
        ], $params);

        $payload['client_phone_number'] = $this->normalizePhone($payload['client_phone_number'] ?? '');

        $this->validatePayload($payload);

        $body = $this->request('POST', '/v1/payment', $payload);
        $code = (int) ($body['code'] ?? 0);

        if (!in_array($code, self::SUCCESS_CODES, true)) {
            Log::error('CinetPay.init_error', [
                'transaction_id' => $payload['merchant_transaction_id'],
                'code' => $body['code'] ?? null,
                'status' => $body['status'] ?? null,
                'description' => $body['description'] ?? null,
            ]);
            throw new CinetPayException(
                $body['description'] ?? $body['status'] ?? 'Erreur de communication avec CinetPay',
                (string) ($body['code'] ?? 'UNKNOWN')
            );
        }

        $this->logTransaction($payload['merchant_transaction_id'], $payload, $body);
        $this->updateTransactionStatus($payload['merchant_transaction_id'], 'INITIATED', $body);

        return [
            'success' => true,
            'payment_token' => $body['payment_token'] ?? null,
            'payment_url' => $body['payment_url'] ?? null,
            'transaction_id' => $body['transaction_id'] ?? null,
            'merchant_transaction_id' => $payload['merchant_transaction_id'],
            'notify_token' => $body['notify_token'] ?? null,
        ];
    }

    public function verifyPayment(string $identifier): array
    {
        $body = $this->request('GET', '/v1/payment/' . rawurlencode($identifier));
        $code = (int) ($body['code'] ?? 0);

        if (!in_array($code, [200, 100, 2001, 2002], true)) {
            Log::warning('CinetPay.verify_error', [
                'identifier' => $identifier,
                'code' => $body['code'] ?? null,
                'status' => $body['status'] ?? null,
                'description' => $body['description'] ?? null,
            ]);
            return [
                'verified' => false,
                'status' => $body['status'] ?? null,
                'error' => $body['description'] ?? $body['status'] ?? 'Erreur de vérification',
                'code' => $body['code'] ?? null,
            ];
        }

        $status = (string) ($body['status'] ?? '');
        $this->updateTransactionStatus($identifier, $status, $body);

        return [
            'verified' => true,
            'status' => $status,
            'code' => $code,
            'transaction_id' => $body['transaction_id'] ?? null,
            'merchant_transaction_id' => $body['merchant_transaction_id'] ?? $identifier,
            'user' => $body['user'] ?? [],
        ];
    }

    public function isPaymentValid(array $verification): bool
    {
        return ($verification['verified'] ?? false)
            && ($verification['status'] ?? '') === 'SUCCESS';
    }

    public function verifyWebhook(array $payload): array
    {
        $merchantTransactionId = $payload['merchant_transaction_id'] ?? null;
        $notifyToken = $payload['notify_token'] ?? null;

        if (!$merchantTransactionId) {
            return ['valid' => false, 'reason' => 'merchant_transaction_id manquant'];
        }

        $transaction = CinetpayTransaction::where('transaction_id', $merchantTransactionId)->first();

        if (!$transaction) {
            return ['valid' => false, 'reason' => 'transaction inconnue'];
        }

        if (!$notifyToken || !hash_equals((string) $transaction->notify_token, (string) $notifyToken)) {
            return ['valid' => false, 'reason' => 'notify_token invalide'];
        }

        return [
            'valid' => true,
            'transaction' => $transaction,
            'transaction_id' => $payload['transaction_id'] ?? $transaction->cpm_trans_id,
            'merchant_transaction_id' => $merchantTransactionId,
            'user' => $payload['user'] ?? [],
        ];
    }

    public function logWebhook(array $headers, array $payload, ?string $transactionId = null): WebhookLog
    {
        return WebhookLog::create([
            'provider' => 'cinetpay',
            'event' => 'notification',
            'transaction_id' => $transactionId ?? $payload['merchant_transaction_id'] ?? $payload['transaction_id'] ?? null,
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

    private function getAccessToken(): string
    {
        return Cache::remember(
            'cinetpay_token_' . strtolower($this->country),
            now()->addHours(23),
            fn () => $this->authenticate()
        );
    }

    private function authenticate(): string
    {
        $response = Http::acceptJson()
            ->timeout(15)
            ->post("{$this->baseUrl}/v1/oauth/login", [
                'api_key' => $this->apiKey,
                'api_password' => $this->apiPassword,
            ]);

        $body = $response->json() ?? [];
        $token = $body['access_token'] ?? null;

        if ($response->failed() || !$token) {
            Log::error('CinetPay.auth_failed', [
                'code' => $body['code'] ?? null,
                'status' => $body['status'] ?? null,
                'description' => $body['description'] ?? null,
                'http' => $response->status(),
            ]);
            throw new CinetPayException(
                $body['description'] ?? $body['status'] ?? 'Authentification CinetPay échouée. Vérifiez vos clés API.',
                (string) ($body['code'] ?? 'AUTH_ERROR')
            );
        }

        return $token;
    }

    private function request(string $method, string $path, ?array $payload = null): array
    {
        $attempts = 0;

        do {
            $http = Http::withToken($this->getAccessToken())
                ->acceptJson()
                ->timeout(15);

            $response = $method === 'GET'
                ? $http->get("{$this->baseUrl}{$path}")
                : $http->post("{$this->baseUrl}{$path}", $payload ?? []);

            $body = $response->json() ?? [];
            $code = (int) ($body['code'] ?? 0);

            if (in_array($code, self::TOKEN_ERROR_CODES, true)) {
                Cache::forget('cinetpay_token_' . strtolower($this->country));
                $attempts++;
                continue;
            }

            if ($response->failed() || $code === 404) {
                Log::error('CinetPay.request_failed', [
                    'method' => $method,
                    'path' => $path,
                    'http' => $response->status(),
                    'code' => $body['code'] ?? null,
                    'status' => $body['status'] ?? null,
                    'description' => $body['description'] ?? null,
                ]);
                throw new CinetPayException(
                    $body['description'] ?? $body['status'] ?? 'Erreur de communication avec CinetPay',
                    (string) ($body['code'] ?? 'NETWORK_ERROR')
                );
            }

            return $body;
        } while ($attempts < 2);

        throw new CinetPayException('Échec d\'authentification CinetPay', 'TOKEN_ERROR');
    }

    private function normalizePhone(string $phone): string
    {
        $phone = trim($phone);

        if ($phone === '') {
            return '';
        }

        if (str_starts_with($phone, '+')) {
            return preg_replace('/[^\d+]/', '', $phone) ?: '';
        }

        $intlCodes = [
            'CI' => '225', 'BF' => '226', 'SN' => '221', 'ML' => '223',
            'TG' => '228', 'GN' => '224', 'CM' => '237', 'BJ' => '229',
            'CD' => '243', 'NE' => '227',
        ];

        $intlCode = $intlCodes[$this->country] ?? '225';
        $digits = preg_replace('/[^\d]/', '', $phone);

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, $intlCode)) {
            return '+' . $digits;
        }

        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return '+' . $intlCode . $digits;
    }

    private function validatePayload(array $payload): void
    {
        $required = [
            'merchant_transaction_id', 'amount', 'designation',
            'notify_url', 'success_url', 'failed_url',
            'client_email', 'client_first_name', 'client_last_name',
        ];

        foreach ($required as $field) {
            if (empty($payload[$field])) {
                throw new CinetPayException("Le champ '{$field}' est requis", 'VALIDATION_ERROR');
            }
        }

        if ((int) $payload['amount'] < 100 || (int) $payload['amount'] > 2500000) {
            throw new CinetPayException('Le montant doit être compris entre 100 et 2 500 000', 'VALIDATION_ERROR');
        }

        if (strlen((string) $payload['merchant_transaction_id']) > 30) {
            throw new CinetPayException('Transaction ID invalide (max 30 caractères)', 'VALIDATION_ERROR');
        }

        if (filter_var($payload['client_email'], FILTER_VALIDATE_EMAIL) === false) {
            throw new CinetPayException('Email client invalide', 'VALIDATION_ERROR');
        }
    }

    private function logTransaction(string $transactionId, array $request, array $response): void
    {
        try {
            CinetpayTransaction::updateOrCreate(
                ['transaction_id' => $transactionId],
                [
                    'site_id' => config('services.cinetpay.site_id'),
                    'country' => $this->country,
                    'amount' => (int) ($request['amount'] ?? 0),
                    'currency' => $request['currency'] ?? 'XOF',
                    'status' => 'INITIATED',
                    'description' => $request['designation'] ?? null,
                    'customer_name' => trim(
                        ($request['client_first_name'] ?? '') . ' ' . ($request['client_last_name'] ?? '')
                    ) ?: null,
                    'customer_email' => $request['client_email'] ?? null,
                    'customer_phone' => $request['client_phone_number'] ?? null,
                    'notify_token' => $response['notify_token'] ?? null,
                    'payment_token' => $response['payment_token'] ?? null,
                    'payment_url' => $response['payment_url'] ?? null,
                    'raw_request' => $request,
                    'raw_response' => $response,
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
            if ($status === 'SUCCESS') {
                $update['paid_at'] = now();
                $update['cpm_trans_id'] = $data['transaction_id'] ?? null;
                $update['payment_method'] = $data['payment_method'] ?? null;
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
