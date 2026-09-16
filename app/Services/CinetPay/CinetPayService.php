<?php

namespace App\Services\CinetPay;

use App\Models\CinetpayTransaction;
use App\Models\WebhookLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Client CinetPay - API Checkout v2.
 *
 * Authentification par apikey + site_id sur chaque appel (pas d'OAuth).
 * Docs : https://docs.cinetpay.com/api/1.0-fr/checkout/initialisation
 */
class CinetPayService
{
    /** Statuts renvoyés par /payment/check pour un paiement encaissé. */
    private const PAID_STATUSES = ['ACCEPTED', 'VALIDATED', 'SUCCESS'];

    /** Codes de succès à l'initialisation (201 = CREATED). */
    private const INIT_SUCCESS_CODES = ['201', '00'];

    /** Alias tolérés vers les canaux réellement acceptés par l'API v2. */
    private const CHANNEL_ALIASES = [
        'MOBILE' => 'MOBILE_MONEY',
        'MOBILE_MONEY' => 'MOBILE_MONEY',
        'CARD' => 'CREDIT_CARD',
        'CREDIT_CARD' => 'CREDIT_CARD',
        'WALLET' => 'WALLET',
        'ALL' => 'ALL',
    ];

    private string $apiKey;
    private string $siteId;
    private string $secretKey;
    private string $baseUrl;
    private string $country;
    private string $currency;
    private string $defaultChannels;
    private string $lang;

    public function __construct()
    {
        $this->apiKey = (string) config('services.cinetpay.api_key', '');
        $this->siteId = (string) config('services.cinetpay.site_id', '');
        $this->secretKey = (string) config('services.cinetpay.secret_key', '');
        $this->baseUrl = $this->normalizeBaseUrl((string) config('services.cinetpay.base_url', 'https://api-checkout.cinetpay.com/v2'));
        $this->country = strtoupper((string) config('services.cinetpay.country', 'CI'));
        $this->currency = strtoupper((string) config('services.cinetpay.currency', 'XOF'));
        $this->defaultChannels = $this->normalizeChannels((string) config('services.cinetpay.channels', 'ALL'));
        $this->lang = strtolower((string) config('services.cinetpay.lang', 'fr'));
    }

    public function isConfigured(): bool
    {
        return filled($this->apiKey) && filled($this->siteId);
    }

    /**
     * Identifiant marchand unique. L'API v2 limite à 100 caractères
     * alphanumériques ; on reste volontairement court et sans caractère spécial.
     */
    public function generateTransactionId(): string
    {
        return 'TXN' . now()->format('YmdHis') . strtoupper(Str::random(8));
    }

    /**
     * Crée la transaction et renvoie l'URL de paiement hébergée par CinetPay.
     *
     * @param  array  $params  transaction_id, amount, description, notify_url, return_url,
     *                         customer (name/surname/email/phone/address/city/country...),
     *                         channels, metadata, invoice_data
     *
     * @throws CinetPayException
     */
    public function initializePayment(array $params): array
    {
        if (!$this->isConfigured()) {
            throw new CinetPayException(
                'CinetPay n\'est pas configuré (CINETPAY_API_KEY / CINETPAY_SITE_ID manquants).',
                'NOT_CONFIGURED'
            );
        }

        $payload = $this->buildInitPayload($params);
        $this->validatePayload($payload);

        $body = $this->request('/payment', $payload);
        $code = (string) ($body['code'] ?? '');

        if (!in_array($code, self::INIT_SUCCESS_CODES, true)) {
            Log::error('CinetPay.init_error', [
                'transaction_id' => $payload['transaction_id'],
                'code' => $code,
                'message' => $body['message'] ?? null,
                'description' => $body['description'] ?? null,
            ]);

            throw new CinetPayException(
                $this->errorMessage($body),
                $code !== '' ? $code : 'UNKNOWN'
            );
        }

        $data = $body['data'] ?? [];
        $token = $data['payment_token'] ?? $data['token'] ?? null;
        $url = $data['payment_url'] ?? null;

        if (!$url) {
            throw new CinetPayException('CinetPay n\'a pas renvoyé d\'URL de paiement.', 'NO_PAYMENT_URL');
        }

        $this->logTransaction($payload, $body);

        return [
            'success' => true,
            'payment_token' => $token,
            'payment_url' => $url,
            'transaction_id' => $payload['transaction_id'],
            'merchant_transaction_id' => $payload['transaction_id'],
            // Montant réellement débité : peut différer de celui demandé
            // (arrondi au multiple de 5 imposé par CinetPay sur le XOF).
            'amount' => $payload['amount'],
            'currency' => $payload['currency'],
        ];
    }

    /**
     * Vérifie l'état réel d'une transaction auprès de CinetPay.
     * C'est la seule source de vérité : ni le webhook ni le return_url ne suffisent.
     */
    public function verifyPayment(string $transactionId): array
    {
        try {
            $body = $this->request('/payment/check', [
                'transaction_id' => $transactionId,
            ]);
        } catch (CinetPayException $e) {
            return [
                'verified' => false,
                'status' => null,
                'code' => $e->getApiCode(),
                'error' => $e->getMessage(),
            ];
        }

        $code = (string) ($body['code'] ?? '');
        $data = $body['data'] ?? [];

        if ($code !== '00') {
            Log::warning('CinetPay.verify_error', [
                'transaction_id' => $transactionId,
                'code' => $code,
                'message' => $body['message'] ?? null,
            ]);

            // 662 (attente client), 600 (échec) et 627 (annulation) sont des
            // réponses métier légitimes : la transaction existe mais n'est pas payée.
            $status = $this->extractStatus($data) ?: ($body['message'] ?? null);
            $this->updateTransactionStatus($transactionId, $status, $data);

            return [
                'verified' => false,
                'status' => $status,
                'code' => $code,
                'error' => $this->errorMessage($body),
            ];
        }

        $status = $this->extractStatus($data);
        $this->updateTransactionStatus($transactionId, $status, $data);

        return [
            'verified' => true,
            'status' => $status,
            'code' => $code,
            'amount' => $data['amount'] ?? $data['cpm_amount'] ?? null,
            'currency' => $data['currency'] ?? $data['cpm_currency'] ?? null,
            'payment_method' => $data['payment_method'] ?? null,
            'payment_date' => $data['payment_date'] ?? $data['cpm_trans_date'] ?? null,
            'operator_id' => $data['operator_id'] ?? null,
            'metadata' => $data['metadata'] ?? $data['cpm_custom'] ?? null,
            'transaction_id' => $transactionId,
            'merchant_transaction_id' => $transactionId,
            'reference' => $data['operator_id'] ?? $data['cpm_trans_id'] ?? null,
            'raw' => $data,
        ];
    }

    public function isPaymentValid(array $verification): bool
    {
        return ($verification['verified'] ?? false)
            && in_array(strtoupper((string) ($verification['status'] ?? '')), self::PAID_STATUSES, true);
    }

    /**
     * Valide la signature HMAC-SHA256 envoyée par CinetPay dans le header x-token.
     *
     * La chaîne signée est la concaténation, dans cet ordre exact, des champs du
     * POST de notification. Un champ absent compte comme une chaîne vide.
     */
    public function verifyHmac(array $payload, ?string $token): bool
    {
        if (blank($this->secretKey) || blank($token)) {
            return false;
        }

        $fields = [
            'cpm_site_id', 'cpm_trans_id', 'cpm_trans_date', 'cpm_amount', 'cpm_currency',
            'signature', 'payment_method', 'cel_phone_num', 'cpm_phone_prefixe',
            'cpm_language', 'cpm_version', 'cpm_payment_config', 'cpm_page_action',
            'cpm_custom', 'cpm_designation', 'cpm_error_message',
        ];

        $data = '';
        foreach ($fields as $field) {
            $data .= (string) ($payload[$field] ?? '');
        }

        $expected = hash_hmac('sha256', $data, $this->secretKey);

        return hash_equals($expected, trim($token));
    }

    /**
     * Contrôles de cohérence sur une notification reçue, avant tout traitement métier.
     */
    public function verifyWebhook(array $payload, ?string $token = null): array
    {
        $transactionId = $payload['cpm_trans_id'] ?? $payload['transaction_id'] ?? null;

        if (!$transactionId) {
            return ['valid' => false, 'reason' => 'cpm_trans_id manquant'];
        }

        $siteId = (string) ($payload['cpm_site_id'] ?? '');
        if ($siteId !== '' && $siteId !== $this->siteId) {
            return ['valid' => false, 'reason' => 'site_id inattendu'];
        }

        if (filled($this->secretKey) && !$this->verifyHmac($payload, $token)) {
            return ['valid' => false, 'reason' => 'signature HMAC (x-token) invalide'];
        }

        $transaction = CinetpayTransaction::where('transaction_id', $transactionId)->first();

        if (!$transaction) {
            return ['valid' => false, 'reason' => 'transaction inconnue'];
        }

        return [
            'valid' => true,
            'transaction' => $transaction,
            'transaction_id' => $transactionId,
            'merchant_transaction_id' => $transactionId,
        ];
    }

    public function logWebhook(array $headers, array $payload, ?string $transactionId = null): WebhookLog
    {
        return WebhookLog::create([
            'provider' => 'cinetpay',
            'event' => 'notification',
            'transaction_id' => $transactionId ?? $payload['cpm_trans_id'] ?? $payload['transaction_id'] ?? null,
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

    /**
     * Construit le corps de la requête d'initialisation au format attendu par l'API v2
     * (champs client à plat : customer_name, customer_surname, ...).
     */
    private function buildInitPayload(array $params): array
    {
        $customer = $params['customer'] ?? [];

        $payload = [
            'apikey' => $this->apiKey,
            'site_id' => $this->siteId,
            'transaction_id' => $params['transaction_id'] ?? $this->generateTransactionId(),
            'amount' => $this->normalizeAmount($params['amount'] ?? 0),
            'currency' => strtoupper((string) ($params['currency'] ?? $this->currency)),
            'description' => Str::limit((string) ($params['description'] ?? ''), 250, ''),
            'notify_url' => $params['notify_url'] ?? '',
            'return_url' => $params['return_url'] ?? '',
            'channels' => $this->normalizeChannels((string) ($params['channels'] ?? $this->defaultChannels)),
            'lang' => strtolower((string) ($params['lang'] ?? $this->lang)),
        ];

        // Champs client : obligatoires dès que le canal carte est proposé.
        $customerFields = [
            'customer_id' => $customer['id'] ?? null,
            'customer_name' => $customer['name'] ?? $customer['first_name'] ?? null,
            'customer_surname' => $customer['surname'] ?? $customer['last_name'] ?? null,
            'customer_email' => $customer['email'] ?? null,
            'customer_phone_number' => $this->normalizePhone((string) ($customer['phone'] ?? $customer['phone_number'] ?? '')),
            'customer_address' => $customer['address'] ?? null,
            'customer_city' => $customer['city'] ?? null,
            'customer_country' => strtoupper((string) ($customer['country'] ?? $this->country)),
            'customer_state' => $customer['state'] ?? null,
            'customer_zip_code' => $customer['zip_code'] ?? null,
        ];

        foreach ($customerFields as $key => $value) {
            if (filled($value)) {
                $payload[$key] = (string) $value;
            }
        }

        // metadata est une chaîne côté CinetPay (255 caractères max).
        if (filled($params['metadata'] ?? null)) {
            $metadata = $params['metadata'];
            $payload['metadata'] = Str::limit(
                is_string($metadata) ? $metadata : json_encode($metadata, JSON_UNESCAPED_UNICODE),
                250,
                ''
            );
        }

        if (filled($params['invoice_data'] ?? null)) {
            $payload['invoice_data'] = $params['invoice_data'];
        }

        if (filled($params['alternative_currency'] ?? null)) {
            $payload['alternative_currency'] = strtoupper((string) $params['alternative_currency']);
        }

        return $payload;
    }

    private function request(string $path, array $payload): array
    {
        $payload = array_merge([
            'apikey' => $this->apiKey,
            'site_id' => $this->siteId,
        ], $payload);

        try {
            $response = Http::acceptJson()
                ->timeout(20)
                ->retry(2, 500, throw: false)
                ->post($this->baseUrl . $path, $payload);
        } catch (\Throwable $e) {
            Log::error('CinetPay.network_error', ['path' => $path, 'error' => $e->getMessage()]);
            throw new CinetPayException('Impossible de joindre CinetPay. Réessayez dans un instant.', 'NETWORK_ERROR');
        }

        $body = $response->json();

        if (!is_array($body)) {
            Log::error('CinetPay.invalid_response', [
                'path' => $path,
                'http' => $response->status(),
                'body' => Str::limit($response->body(), 500),
            ]);
            throw new CinetPayException('Réponse illisible de CinetPay.', 'INVALID_RESPONSE');
        }

        // Une erreur HTTP sans code métier exploitable n'est pas récupérable côté appelant.
        if ($response->serverError() && !isset($body['code'])) {
            throw new CinetPayException('CinetPay est momentanément indisponible.', 'SERVER_ERROR');
        }

        return $body;
    }

    private function errorMessage(array $body): string
    {
        return $body['description']
            ?? $body['message']
            ?? 'Erreur de communication avec CinetPay';
    }

    private function extractStatus(array $data): ?string
    {
        $status = $data['status'] ?? $data['cpm_trans_status'] ?? null;

        return $status !== null ? strtoupper((string) $status) : null;
    }

    private function normalizeBaseUrl(string $url): string
    {
        $url = rtrim(trim($url), '/');

        // Tolère une valeur d'environnement sans le suffixe de version.
        if (!str_ends_with($url, '/v2')) {
            $url .= '/v2';
        }

        return $url;
    }

    private function normalizeChannels(string $channels): string
    {
        $normalized = [];

        foreach (explode(',', strtoupper($channels)) as $channel) {
            $channel = trim($channel);

            if ($channel === '') {
                continue;
            }

            $normalized[] = self::CHANNEL_ALIASES[$channel] ?? $channel;
        }

        return $normalized === [] ? 'ALL' : implode(',', array_unique($normalized));
    }

    /**
     * CinetPay refuse les montants non entiers, et exige un multiple de 5
     * pour les devises d'Afrique de l'Ouest et centrale.
     */
    private function normalizeAmount(int|float|string $amount): int
    {
        $amount = (int) round((float) $amount);

        if (in_array($this->currency, ['XOF', 'XAF', 'CDF', 'GNF'], true) && $amount % 5 !== 0) {
            $amount = (int) (ceil($amount / 5) * 5);
        }

        return $amount;
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
        foreach (['transaction_id', 'amount', 'description', 'notify_url', 'return_url'] as $field) {
            if (empty($payload[$field])) {
                throw new CinetPayException("Le champ '{$field}' est requis", 'VALIDATION_ERROR');
            }
        }

        $min = (int) config('services.cinetpay.min_amount', 100);
        $max = (int) config('services.cinetpay.max_amount', 2500000);

        if ($payload['amount'] < $min || $payload['amount'] > $max) {
            throw new CinetPayException(
                sprintf('Le montant doit être compris entre %s et %s %s', $min, $max, $payload['currency']),
                'VALIDATION_ERROR'
            );
        }

        if (!preg_match('/^[A-Za-z0-9_-]{1,100}$/', (string) $payload['transaction_id'])) {
            throw new CinetPayException('Transaction ID invalide (alphanumérique, 100 caractères max)', 'VALIDATION_ERROR');
        }

        foreach (['notify_url', 'return_url'] as $urlField) {
            if (filter_var($payload[$urlField], FILTER_VALIDATE_URL) === false) {
                throw new CinetPayException("L'URL '{$urlField}' est invalide", 'VALIDATION_ERROR');
            }
        }

        if (filled($payload['customer_email'] ?? null)
            && filter_var($payload['customer_email'], FILTER_VALIDATE_EMAIL) === false) {
            throw new CinetPayException('Email client invalide', 'VALIDATION_ERROR');
        }
    }

    private function logTransaction(array $request, array $response): void
    {
        try {
            $data = $response['data'] ?? [];

            CinetpayTransaction::updateOrCreate(
                ['transaction_id' => $request['transaction_id']],
                [
                    'site_id' => $this->siteId,
                    'country' => $request['customer_country'] ?? $this->country,
                    'amount' => (int) ($request['amount'] ?? 0),
                    'currency' => $request['currency'] ?? $this->currency,
                    'status' => 'CREATED',
                    'description' => $request['description'] ?? null,
                    'customer_name' => trim(
                        ($request['customer_name'] ?? '') . ' ' . ($request['customer_surname'] ?? '')
                    ) ?: null,
                    'customer_email' => $request['customer_email'] ?? null,
                    'customer_phone' => $request['customer_phone_number'] ?? null,
                    'payment_token' => $data['payment_token'] ?? $data['token'] ?? null,
                    'payment_url' => $data['payment_url'] ?? null,
                    // La clé API ne doit jamais atterrir en base.
                    'raw_request' => Arr::except($request, ['apikey']),
                    'raw_response' => $response,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('CinetPay.log_transaction_error', [
                'transaction_id' => $request['transaction_id'] ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function updateTransactionStatus(string $transactionId, ?string $status, array $data): void
    {
        try {
            $update = ['status' => $status ?? 'UNKNOWN'];

            if ($status !== null && in_array($status, self::PAID_STATUSES, true)) {
                $update['paid_at'] = now();
                $update['payment_method'] = $data['payment_method'] ?? null;
                $update['cpm_trans_id'] = $data['operator_id'] ?? $data['cpm_trans_id'] ?? null;
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
