<?php

namespace App\Services\Botisimo;

use App\Models\ShopBot;
use App\Services\Botisimo\Exceptions\BotisimoNotFoundException;
use App\Support\PhoneNumber;

/**
 * Clients d'un bot, conservés uniquement chez Botisimo.
 *
 * Les routes /v1/clients/:id ne prennent pas de bot_id et la clé API est commune
 * à toutes les boutiques : l'appartenance est vérifiée ici avant toute lecture
 * ou écriture, et refusée si elle ne peut pas être prouvée.
 */
class ClientDirectory
{
    public const PAGE_SIZE = 50;

    private const AUDIENCE_PAGE_SIZE = 200;

    public function __construct(private BotisimoClient $client) {}

    /** @return array{clients: array<int, array>, has_more: bool} */
    public function list(ShopBot $bot, array $filters, int $page): array
    {
        $response = $this->client->listClients(array_filter([
            'bot_id' => $bot->botisimo_bot_id,
            'search' => $filters['search'] ?? null,
            'tag' => $filters['tag'] ?? null,
            'opted_out' => isset($filters['opted_out']) ? ($filters['opted_out'] ? 'true' : 'false') : null,
            'limit' => self::PAGE_SIZE,
            'offset' => ($page - 1) * self::PAGE_SIZE,
        ], fn ($value) => $value !== null && $value !== ''));

        $rows = BotisimoPayload::list($response, 'clients', 'items', 'data') ?? [];

        return [
            'clients' => array_values(array_map(fn ($row) => self::normalize($row), array_filter($rows, 'is_array'))),
            'has_more' => count($rows) === self::PAGE_SIZE,
        ];
    }

    public function findOwned(ShopBot $bot, string $clientId): array
    {
        $response = $this->client->getClient($clientId);
        $client = self::normalize(is_array($response['client'] ?? null) ? $response['client'] : (is_array($response['data'] ?? null) ? $response['data'] : $response));

        if ($client['bot_id'] === null || $client['bot_id'] !== (string) $bot->botisimo_bot_id) {
            throw new BotisimoNotFoundException('Client introuvable pour ce bot.', 404);
        }

        return $client;
    }

    public function create(ShopBot $bot, array $data): void
    {
        $this->client->createClient([
            'bot_id' => $bot->botisimo_bot_id,
            'phone' => self::phone($data['phone']),
            'name' => $data['name'] ?? null,
            'tags' => $data['tags'] ?? [],
        ]);
    }

    public function update(ShopBot $bot, string $clientId, array $data): void
    {
        $this->findOwned($bot, $clientId);
        $this->client->updateClient($clientId, $data);
    }

    public function delete(ShopBot $bot, string $clientId): void
    {
        $this->findOwned($bot, $clientId);
        $this->client->deleteClient($clientId);
    }

    /**
     * Numéros joignables, sans doublon. La lecture s'arrête dès que $limit est
     * dépassé : la campagne sera refusée, inutile de consommer plus de requêtes.
     *
     * @param  array<int, string>  $tags  vide pour tous les clients
     * @return array<int, string>
     */
    public function audience(ShopBot $bot, array $tags, int $limit): array
    {
        $phones = [];

        foreach ($tags === [] ? [null] : $tags as $tag) {
            for ($offset = 0; count($phones) <= $limit; $offset += self::AUDIENCE_PAGE_SIZE) {
                $response = $this->client->listClients(array_filter([
                    'bot_id' => $bot->botisimo_bot_id,
                    'tag' => $tag,
                    'opted_out' => 'false',
                    'limit' => self::AUDIENCE_PAGE_SIZE,
                    'offset' => $offset,
                ], fn ($value) => $value !== null));

                $rows = BotisimoPayload::list($response, 'clients', 'items', 'data') ?? [];
                $before = count($phones);

                foreach ($rows as $row) {
                    $client = is_array($row) ? self::normalize($row) : null;

                    // Revérifié ici : un désinscrit ne doit jamais dépendre du seul filtre distant.
                    if ($client !== null && ! $client['opted_out'] && $client['phone'] !== null) {
                        $phones[$client['phone']] = $client['phone'];
                    }
                }

                if (count($rows) < self::AUDIENCE_PAGE_SIZE || count($phones) === $before) {
                    break;
                }
            }
        }

        return array_values($phones);
    }

    public function isOptedOut(ShopBot $bot, string $phone): bool
    {
        $response = $this->client->listClients(['bot_id' => $bot->botisimo_bot_id, 'search' => self::phone($phone)]);

        foreach (BotisimoPayload::list($response, 'clients', 'items', 'data') ?? [] as $row) {
            $client = is_array($row) ? self::normalize($row) : null;

            if ($client !== null && $client['phone'] === self::phone($phone) && $client['opted_out']) {
                return true;
            }
        }

        return false;
    }

    /** E.164 sans le +, le format attendu par Botisimo. */
    public static function phone(?string $phone): string
    {
        return ltrim(PhoneNumber::toE164($phone), '+');
    }

    private static function normalize(array $row): array
    {
        $phone = $row['phone'] ?? $row['phone_number'] ?? null;
        $tags = $row['tags'] ?? [];
        $botId = $row['bot_id'] ?? null;

        return [
            'id' => isset($row['id']) ? (string) $row['id'] : null,
            'bot_id' => is_scalar($botId) ? (string) $botId : null,
            'phone' => is_scalar($phone) && $phone !== '' ? self::phone((string) $phone) : null,
            'name' => is_string($row['name'] ?? null) ? $row['name'] : null,
            'tags' => is_array($tags) ? array_values(array_filter($tags, 'is_string')) : [],
            'opted_out' => (bool) ($row['opted_out'] ?? $row['opt_out'] ?? false),
        ];
    }
}
