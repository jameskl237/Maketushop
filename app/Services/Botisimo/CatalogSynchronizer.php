<?php

namespace App\Services\Botisimo;

use App\Jobs\Botisimo\SyncProductToBotisimo;
use App\Models\Product;
use App\Models\Service;
use App\Models\ShopBot;
use App\Models\ShopBotProduct;
use App\Services\Botisimo\Exceptions\BotisimoException;
use App\Services\Botisimo\Exceptions\BotisimoNotFoundException;
use App\Services\Botisimo\Exceptions\BotisimoRateLimitException;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Log;

/**
 * Tient le catalogue Botisimo aligné sur les produits et services de la boutique.
 *
 * Une erreur API est notée sur la ligne au lieu d'être rejouée par la queue :
 * rejouer un POST après un timeout créerait un doublon, et la réconciliation de
 * nuit retente de toute façon les lignes en erreur. Seul le rate limit remonte,
 * pour que le job se relâche.
 */
class CatalogSynchronizer
{
    private const PAGE_SIZE = 200;

    public function __construct(
        private BotisimoClient $client,
        private CatalogMapper $mapper,
    ) {}

    public static function queue(Product|Service $model, ?int $shopId): void
    {
        if ($shopId === null) {
            return;
        }

        $botId = ShopBot::where('shop_id', $shopId)
            ->where('channel', ShopBot::CHANNEL_WHATSAPP)
            ->whereNotNull('botisimo_bot_id')
            ->value('id');

        if ($botId !== null) {
            SyncProductToBotisimo::dispatch($botId, $model->getMorphClass(), $model->getKey());
        }
    }

    public function sync(ShopBot $bot, string $type, int $id): void
    {
        $model = Relation::getMorphedModel($type)::find($id);
        $item = ShopBotProduct::firstOrNew([
            'shop_bot_id' => $bot->id,
            'catalogable_type' => $type,
            'catalogable_id' => $id,
        ]);

        if ($model === null || (int) $model->shop_id !== (int) $bot->shop_id) {
            $this->remove($item);

            return;
        }

        if ($this->isUpToDate($item, $model)) {
            return;
        }

        $this->push($bot, $item, $this->mapper->map($model, (bool) $item->is_ad));
    }

    private function isUpToDate(ShopBotProduct $item, Product|Service $model): bool
    {
        return filled($item->botisimo_product_id)
            && $item->sync_error === null
            && $item->sync_hash === $this->hash($this->mapper->map($model, (bool) $item->is_ad));
    }

    private function hash(array $payload): string
    {
        return hash('sha256', json_encode($payload));
    }

    private function push(ShopBot $bot, ShopBotProduct $item, array $payload): void
    {
        try {
            $remoteId = $this->createOrUpdate($bot, $item, $payload);
        } catch (BotisimoRateLimitException $e) {
            throw $e;
        } catch (BotisimoException $e) {
            $item->fill(['sync_error' => $e->userMessage()])->save();

            return;
        }

        $item->fill([
            'botisimo_product_id' => $remoteId,
            'sync_hash' => $this->hash($payload),
            'synced_at' => now(),
            'sync_error' => null,
        ])->save();
    }

    private function createOrUpdate(ShopBot $bot, ShopBotProduct $item, array $payload): string
    {
        if (filled($item->botisimo_product_id)) {
            try {
                $this->client->updateCatalogProduct($item->botisimo_product_id, $payload);

                return $item->botisimo_product_id;
            } catch (BotisimoNotFoundException) {
                // Supprimé côté Botisimo : on le recrée.
            }
        }

        $response = $this->client->createCatalogProduct(['bot_id' => $bot->botisimo_bot_id] + $payload);
        $remoteId = BotisimoPayload::get($response, 'id', 'product_id');

        if ($remoteId === null) {
            throw new BotisimoException('Réponse de création de produit Botisimo sans identifiant.');
        }

        return (string) $remoteId;
    }

    private function remove(ShopBotProduct $item): void
    {
        if (filled($item->botisimo_product_id)) {
            try {
                $this->client->deleteCatalogProduct($item->botisimo_product_id);
            } catch (BotisimoNotFoundException) {
                // Déjà absent : le résultat voulu.
            } catch (BotisimoRateLimitException $e) {
                throw $e;
            } catch (BotisimoException $e) {
                $item->fill(['sync_error' => $e->userMessage()])->save();

                return;
            }
        }

        if ($item->exists) {
            $item->delete();
        }
    }

    /** Met en file ce qui a changé, y compris les suppressions en cascade SQL sans observer. */
    public function queueChanges(ShopBot $bot): int
    {
        $items = $bot->catalogItems()->get()->keyBy(fn ($item) => "{$item->catalogable_type}:{$item->catalogable_id}");
        $seen = [];
        $queued = 0;

        foreach ([Product::class, Service::class] as $class) {
            $models = $class::with(['category', 'medias'])->where('shop_id', $bot->shop_id)->lazyById();

            foreach ($models as $model) {
                $key = "{$model->getMorphClass()}:{$model->getKey()}";
                $seen[$key] = true;

                if ($items->has($key) && $this->isUpToDate($items[$key], $model)) {
                    continue;
                }

                SyncProductToBotisimo::dispatch($bot->id, $model->getMorphClass(), $model->getKey());
                $queued++;
            }
        }

        foreach ($items as $key => $item) {
            if (! isset($seen[$key])) {
                SyncProductToBotisimo::dispatch($bot->id, $item->catalogable_type, $item->catalogable_id);
                $queued++;
            }
        }

        return $queued;
    }

    /**
     * Compare le catalogue distant aux liens locaux. Sans format de réponse
     * documenté, on préfère ne rien faire que de recréer tout un catalogue sur
     * une lecture erronée : d'où les arrêts sur liste illisible ou sans aucun
     * identifiant commun.
     *
     * @return array{missing:int, orphans:int}|null null si la comparaison a été abandonnée
     */
    public function reconcile(ShopBot $bot, bool $prune = false): ?array
    {
        $remoteIds = $this->remoteIds($bot);

        if ($remoteIds === null) {
            Log::warning('Catalogue Botisimo illisible, réconciliation abandonnée', ['shop_bot_id' => $bot->id]);

            return null;
        }

        $localIds = $bot->catalogItems()->whereNotNull('botisimo_product_id')->pluck('botisimo_product_id')->all();

        if ($localIds !== [] && $remoteIds !== [] && array_intersect($localIds, $remoteIds) === []) {
            Log::warning('Aucun identifiant commun avec le catalogue Botisimo, réconciliation abandonnée', ['shop_bot_id' => $bot->id]);

            return null;
        }

        $missing = array_values(array_diff($localIds, $remoteIds));
        $orphans = array_values(array_diff($remoteIds, $localIds));

        $bot->catalogItems()->whereIn('botisimo_product_id', $missing)
            ->update(['botisimo_product_id' => null, 'sync_hash' => null]);

        if ($prune) {
            foreach ($orphans as $orphan) {
                $this->client->deleteCatalogProduct($orphan);
            }
        } elseif ($orphans !== []) {
            Log::warning('Entrées du catalogue Botisimo inconnues localement', ['shop_bot_id' => $bot->id, 'count' => count($orphans)]);
        }

        return ['missing' => count($missing), 'orphans' => count($orphans)];
    }

    /** @return array<int, string>|null */
    private function remoteIds(ShopBot $bot): ?array
    {
        $ids = [];

        for ($offset = 0; ; $offset += self::PAGE_SIZE) {
            $response = $this->client->listCatalog($bot->botisimo_bot_id, ['limit' => self::PAGE_SIZE, 'offset' => $offset]);
            $rows = BotisimoPayload::list($response, 'products', 'items', 'catalog', 'data');

            if ($rows === null) {
                return null;
            }

            $countBefore = count($ids);

            foreach ($rows as $row) {
                $id = is_array($row) ? BotisimoPayload::get($row, 'id', 'product_id') : null;

                if ($id === null) {
                    return null;
                }

                $ids[(string) $id] = (string) $id;
            }

            // La seconde condition évite une boucle infinie si `offset` est ignoré.
            if (count($rows) < self::PAGE_SIZE || count($ids) === $countBefore) {
                return array_values($ids);
            }
        }
    }
}
