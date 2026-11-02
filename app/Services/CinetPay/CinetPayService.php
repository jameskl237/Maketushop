<?php

namespace App\Services\CinetPay;

use App\Models\CinetpayTransaction;
use App\Models\Payment;
use App\Models\WebhookLog;
use App\Support\PhoneNumber;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Client CinetPay - API v1 (OAuth).
 *
 * Endpoint vérifié en production : https://api.cinetpay.co/v1
 * L'hôte api-checkout.cinetpay.com de l'ancienne doc Checkout v2 n'existe plus
 * (NXDOMAIN jusqu'aux serveurs de noms du domaine).
 *
 * Authentification : api_key + api_password -> jeton Bearer mis en cache.
 */
class CinetPayService
{
    private string $apiKey;
    private string $apiPassword;
    private string $baseUrl;
    private string $country;
    private string $currency;

    private const SUCCESS_CODES = [200, 100];
    private const TOKEN_ERROR_CODES = [1002, 1003];

    public function __construct()
    {
        $this->apiKey = (string) config('services.cinetpay.api_key', '');
        $this->apiPassword = (string) config('services.cinetpay.api_password', '');
        $this->baseUrl = $this->resolveBaseUrl((string) config('services.cinetpay.base_url', ''));
        $this->country = strtoupper((string) config('services.cinetpay.country', 'CM'));
        $this->currency = strtoupper((string) config('services.cinetpay.currency', 'XAF'));
    }

    public const PRODUCTION_URL = 'https://api.cinetpay.co';
    public const SANDBOX_URL = 'https://api.cinetpay.net';

    /**
     * Une clé sk_test_ n'est reconnue que par la sandbox, une clé sk_live_ que
     * par la production : un mauvais couple clé/URL donne INVALID_CREDENTIALS.
     */
    private function resolveBaseUrl(string $configured): string
    {
        $expected = match (true) {
            str_starts_with($this->apiKey, 'sk_test_') => self::SANDBOX_URL,
            str_starts_with($this->apiKey, 'sk_live_') => self::PRODUCTION_URL,
            default => null,
        };

        $configured = rtrim(trim($configured), '/');

        if ($configured === '') {
            return $expected ?? self::PRODUCTION_URL;
        }

        if ($expected !== null && $configured !== $expected) {
            Log::warning('CinetPay.base_url_mismatch', [
                'configured' => $configured,
                'expected_for_key' => $expected,
            ]);
        }

        return $configured;
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    public function isSandbox(): bool
    {
        return $this->baseUrl === self::SANDBOX_URL;
    }

    /**
     * Le jeton dépend du couple clé/URL : après un changement de clé ou
     * d'environnement, l'ancien jeton ne doit pas être réutilisé.
     */
    private function tokenCacheKey(): string
    {
        return 'cinetpay_token_' . sha1($this->baseUrl . '|' . $this->apiKey . '|' . $this->country);
    }

    /** Teste l'authentification sans passer par le cache (diagnostic). */
    public function checkCredentials(): string
    {
        Cache::forget($this->tokenCacheKey());

        return $this->getAccessToken();
    }

    public function isConfigured(): bool
    {
        return filled($this->apiKey) && filled($this->apiPassword);
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function country(): string
    {
        return $this->country;
    }

    public function generateTransactionId(): string
    {
        return 'TXN-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(8));
    }

    public function initializePayment(array $params): array
    {
        if (!$this->isConfigured()) {
            throw new CinetPayException(
                'CinetPay n\'est pas configuré : renseignez CINETPAY_API_KEY et CINETPAY_API_PASSWORD '
                . '(page Intégration du back-office CinetPay).',
                'NOT_CONFIGURED'
            );
        }

        $payload = array_merge([
            'currency' => $this->currency,
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

        if (!$transaction && !Payment::where('transaction_id', $merchantTransactionId)->exists()) {
            return ['valid' => false, 'reason' => 'transaction inconnue'];
        }

        if ($transaction && filled($transaction->notify_token)) {
            if (!$notifyToken || !hash_equals((string) $transaction->notify_token, (string) $notifyToken)) {
                return ['valid' => false, 'reason' => 'notify_token invalide'];
            }
        } else {
            // Transactions créées avant le rétablissement de la colonne notify_token :
            // on ne peut pas authentifier la notification, mais elle ne déclenche
            // qu'un appel à l'API de vérification, qui seul fait foi.
            Log::warning('CinetPay.webhook_without_notify_token', ['transaction_id' => $merchantTransactionId]);
        }

        return [
            'valid' => true,
            'transaction' => $transaction,
            'transaction_id' => $payload['transaction_id'] ?? $transaction?->cpm_trans_id,
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
            $this->tokenCacheKey(),
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
                ->timeout(20);

            try {
                $response = $method === 'GET'
                    ? $http->get("{$this->baseUrl}{$path}")
                    : $http->post("{$this->baseUrl}{$path}", $payload ?? []);
            } catch (\Throwable $e) {
                Log::error('CinetPay.network_error', [
                    'path' => $path,
                    'base_url' => $this->baseUrl,
                    'error' => $e->getMessage(),
                ]);
                throw new CinetPayException(
                    'Impossible de joindre CinetPay (' . $this->baseUrl . '). Vérifiez CINETPAY_API_URL et la connexion réseau.',
                    'NETWORK_ERROR'
                );
            }

            $body = $response->json() ?? [];
            $code = (int) ($body['code'] ?? 0);

            if (in_array($code, self::TOKEN_ERROR_CODES, true)) {
                Cache::forget($this->tokenCacheKey());
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
        return PhoneNumber::toE164($phone, $this->country);
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
                    'currency' => $request['currency'] ?? $this->currency,
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
                    'raw_request' => Arr::except($request, ['apikey', 'api_key', 'api_password']),
                    'raw_response' => $response,
                ]
            );
        } catch (\Throwable $e) {
            // Sans cette ligne, le notify_token est perdu et le webhook ne peut plus
            // être authentifié : on bloque plutôt que d'envoyer le client payer.
            Log::error('CinetPay.log_transaction_error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);
            throw new CinetPayException(
                'Impossible d\'enregistrer la transaction CinetPay (base de données). Lancez `php artisan migrate`.',
                'DB_ERROR'
            );
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
