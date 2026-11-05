<?php

namespace App\Jobs\Botisimo;

use App\Models\ShopBot;
use App\Services\Botisimo\CatalogSynchronizer;
use App\Services\Botisimo\Exceptions\BotisimoException;
use App\Services\Botisimo\Exceptions\BotisimoRateLimitException;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

class ReconcileShopCatalog implements ShouldQueue
{
    use Queueable;

    public int $maxExceptions = 3;

    public function __construct(
        public int $shopBotId,
        public bool $prune = false,
    ) {}

    public function retryUntil(): DateTimeInterface
    {
        return now()->addHours(12);
    }

    public function middleware(): array
    {
        return [
            new RateLimited('botisimo-background'),
            (new WithoutOverlapping("reconcile:{$this->shopBotId}"))->dontRelease()->expireAfter(600),
        ];
    }

    public function handle(CatalogSynchronizer $synchronizer): void
    {
        $bot = ShopBot::find($this->shopBotId);

        if ($bot === null || ! $bot->isProvisioned()) {
            return;
        }

        try {
            $synchronizer->reconcile($bot, $this->prune);
        } catch (BotisimoRateLimitException $e) {
            $this->release($e->retryAfter);

            return;
        } catch (BotisimoException $e) {
            // La partie locale reste utile même si la lecture distante a échoué.
            Log::warning('Réconciliation du catalogue Botisimo échouée', ['shop_bot_id' => $bot->id, 'status' => $e->status]);
        }

        $synchronizer->queueChanges($bot);
        $bot->update(['last_synced_at' => now()]);
    }
}
