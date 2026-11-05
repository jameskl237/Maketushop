<?php

namespace App\Services\Botisimo;

use App\Services\Botisimo\Exceptions\BotisimoConfigurationException;
use App\Services\Botisimo\Exceptions\BotisimoConnectionException;
use App\Services\Botisimo\Exceptions\BotisimoException;
use App\Services\Botisimo\Exceptions\BotisimoMetaException;
use App\Services\Botisimo\Exceptions\BotisimoRateLimitException;
use App\Services\Botisimo\Exceptions\BotisimoServerException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Sleep;

/**
 * Client unique de l'API REST Botisimo : chaque méthode publique correspond à
 * un endpoint documenté. Tous les appels partagent le même budget de requêtes.
 *
 * Reprises : un 429 est rejoué quelle que soit la méthode, car la requête a été
 * refusée. Un 500, un 502 ou un timeout ne sont rejoués que sur GET, PUT et
 * DELETE : rejouer un POST pourrait envoyer deux fois un message et le facturer
 * deux fois.
 */
class BotisimoClient
{
    private const RATE_LIMIT_KEY = 'botisimo:api';

    private const IDEMPOTENT_METHODS = ['GET', 'PUT', 'DELETE'];

    // --- Bots ---

    public function listBots(): array
    {
        return $this->request('GET', '/v1/bots');
    }

    public function getBot(string $botId): array
    {
        return $this->request('GET', $this->path('bots', $botId));
    }

    public function createBot(array $data): array
    {
        return $this->request('POST', '/v1/bots', json: $data);
    }

    public function updateBot(string $botId, array $data): array
    {
        return $this->request('PUT', $this->path('bots', $botId), json: $data);
    }

    public function deleteBot(string $botId): array
    {
        return $this->request('DELETE', $this->path('bots', $botId));
    }

    public function activateBot(string $botId): array
    {
        return $this->request('POST', $this->path('bots', $botId, 'activate'));
    }

    public function deactivateBot(string $botId): array
    {
        return $this->request('POST', $this->path('bots', $botId, 'deactivate'));
    }

    public function connectBot(string $botId, string $callbackUrl): array
    {
        return $this->request('POST', $this->path('bots', $botId, 'connect'), json: ['callback_url' => $callbackUrl]);
    }

    // --- Messages et marketing ---

    public function sendMessage(string $botId, array $data): array
    {
        return $this->request('POST', $this->path('messages', $botId, 'send'), json: $data);
    }

    public function sendMarketing(array $data): array
    {
        return $this->request('POST', '/v1/marketing/send', json: $data);
    }

    public function broadcast(array $data): array
    {
        return $this->request('POST', '/v1/marketing/broadcast', json: $data);
    }

    public function listCampaigns(array $query = []): array
    {
        return $this->request('GET', '/v1/marketing/campaigns', $query);
    }

    // --- Conversations ---

    public function listConversations(array $query = []): array
    {
        return $this->request('GET', '/v1/conversations', $query);
    }

    public function getConversation(string $conversationId): array
    {
        return $this->request('GET', $this->path('conversations', $conversationId));
    }

    public function listConversationMessages(string $conversationId, array $query = []): array
    {
        return $this->request('GET', $this->path('conversations', $conversationId, 'messages'), $query);
    }

    public function setConversationAi(string $conversationId, bool $enabled): array
    {
        return $this->request('PUT', $this->path('conversations', $conversationId, 'ai'), json: ['ai_enabled' => $enabled]);
    }

    // --- Templates ---

    public function listTemplates(string $botId): array
    {
        return $this->request('GET', '/v1/templates', ['bot_id' => $botId]);
    }

    public function createTemplate(array $data): array
    {
        return $this->request('POST', '/v1/templates', json: $data);
    }

    public function deleteTemplate(string $templateId): array
    {
        return $this->request('DELETE', $this->path('templates', $templateId));
    }

    // --- Catalogue ---

    public function listCatalog(string $botId, array $query = []): array
    {
        return $this->request('GET', '/v1/catalog', ['bot_id' => $botId] + $query);
    }

    public function createCatalogProduct(array $data): array
    {
        return $this->request('POST', '/v1/catalog', json: $data);
    }

    public function updateCatalogProduct(string $productId, array $data): array
    {
        return $this->request('PUT', $this->path('catalog', $productId), json: $data);
    }

    public function deleteCatalogProduct(string $productId): array
    {
        return $this->request('DELETE', $this->path('catalog', $productId));
    }

    public function setCatalogAi(string $botId, bool $enabled): array
    {
        return $this->request('PUT', $this->path('catalog', $botId, 'ai'), json: ['enabled' => $enabled]);
    }

    // --- Clients ---

    public function listClients(array $query = []): array
    {
        return $this->request('GET', '/v1/clients', $query);
    }

    public function getClient(string $clientId): array
    {
        return $this->request('GET', $this->path('clients', $clientId));
    }

    public function createClient(array $data): array
    {
        return $this->request('POST', '/v1/clients', json: $data);
    }

    public function updateClient(string $clientId, array $data): array
    {
        return $this->request('PUT', $this->path('clients', $clientId), json: $data);
    }

    public function deleteClient(string $clientId): array
    {
        return $this->request('DELETE', $this->path('clients', $clientId));
    }

    // --- Webhooks ---

    /** La réponse contient le secret, affiché une seule fois. */
    public function createWebhook(string $botId, string $url, array $events): array
    {
        return $this->request('POST', '/v1/webhooks', json: ['bot_id' => $botId, 'url' => $url, 'events' => $events]);
    }

    public function listWebhooks(): array
    {
        return $this->request('GET', '/v1/webhooks');
    }

    public function deleteWebhook(string $botId): array
    {
        return $this->request('DELETE', $this->path('webhooks', $botId));
    }

    // --- Crédits ---

    public function creditBalance(): array
    {
        return $this->request('GET', '/v1/credits/balance');
    }

    public function rechargeCredits(array $data): array
    {
        return $this->request('POST', '/v1/credits/recharge', json: $data);
    }

    // --- Transport ---

    private function path(string ...$segments): string
    {
        return '/v1/'.implode('/', array_map('rawurlencode', $segments));
    }

    private function request(string $method, string $path, array $query = [], ?array $json = null): array
    {
        $apiKey = (string) config('botisimo.api_key');

        if ($apiKey === '') {
            throw new BotisimoConfigurationException('BOTISIMO_API_KEY n\'est pas configurée.');
        }

        $maxAttempts = max(1, (int) config('botisimo.retry.max_attempts'));

        for ($attempt = 1; ; $attempt++) {
            try {
                return $this->attempt($apiKey, $method, $path, $query, $json);
            } catch (BotisimoRateLimitException $e) {
                // Budget local épuisé (status null) : attendre bloquerait une requête web.
                if ($e->status === null || $attempt >= $maxAttempts || $e->retryAfter > config('botisimo.retry.max_wait_seconds')) {
                    throw $e;
                }

                Sleep::for($e->retryAfter)->seconds();
            } catch (BotisimoServerException|BotisimoMetaException|BotisimoConnectionException $e) {
                if ($attempt >= $maxAttempts || ! in_array($method, self::IDEMPOTENT_METHODS, true)) {
                    throw $e;
                }

                Sleep::for((int) config('botisimo.retry.base_delay_ms') * (2 ** ($attempt - 1)))->milliseconds();
            }
        }
    }

    private function attempt(string $apiKey, string $method, string $path, array $query, ?array $json): array
    {
        $this->consumeBudget();

        $options = array_filter([
            'query' => array_filter($query, fn ($value) => $value !== null),
            'json' => $json,
        ], fn ($value) => $value !== null && $value !== []);

        $startedAt = microtime(true);

        try {
            $response = Http::baseUrl(rtrim((string) config('botisimo.base_url'), '/'))
                ->withHeaders(['x-api-key' => $apiKey])
                ->acceptJson()
                ->timeout((int) config('botisimo.timeout'))
                ->connectTimeout((int) config('botisimo.connect_timeout'))
                ->withoutRedirecting() // la clé API ne doit pas suivre une redirection
                ->send($method, ltrim($path, '/'), $options);
        } catch (ConnectionException $e) {
            Log::warning('Botisimo injoignable', compact('method', 'path'));

            throw new BotisimoConnectionException('Botisimo est injoignable.', null, $e);
        }

        $this->log($method, $path, $response, $startedAt);

        if ($response->status() === 429) {
            throw new BotisimoRateLimitException(retryAfter: max(1, (int) ($response->header('Retry-After') ?: 10)));
        }

        if (! $response->successful()) {
            throw BotisimoException::fromResponse($response);
        }

        return $response->json() ?? [];
    }

    private function consumeBudget(): void
    {
        $window = (int) config('botisimo.rate_limit.window_seconds');
        $hits = RateLimiter::hit(self::RATE_LIMIT_KEY, $window);

        if ($hits > (int) config('botisimo.rate_limit.max_requests')) {
            throw new BotisimoRateLimitException(
                'Budget de requêtes Botisimo de la plateforme épuisé.',
                null,
                retryAfter: max(1, RateLimiter::availableIn(self::RATE_LIMIT_KEY)),
            );
        }
    }

    /** Ni clé, ni corps, ni query string : uniquement des identifiants techniques. */
    private function log(string $method, string $path, Response $response, float $startedAt): void
    {
        Log::log($response->successful() ? 'debug' : 'warning', 'Appel API Botisimo', [
            'method' => $method,
            'path' => $path,
            'status' => $response->status(),
            'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
        ]);
    }
}
