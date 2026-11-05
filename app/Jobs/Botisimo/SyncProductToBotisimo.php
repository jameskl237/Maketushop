<?php

namespace App\Jobs\Botisimo;

use App\Models\ShopBot;
use App\Services\Botisimo\CatalogSynchronizer;
use App\Services\Botisimo\Exceptions\BotisimoRateLimitException;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;

/**
 * Lit l'état courant au moment de son exécution : plusieurs modifications
 * rapprochées (produit puis ses photos) se résument à un seul envoi, et une
 * suppression passe par le même chemin.
 */
class SyncProductToBotisimo implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 3600;

    public int $maxExceptions = 3;

    public function __construct(
        public int $shopBotId,
        public string $catalogableType,
        public int $catalogableId,
    ) {
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return "{$this->shopBotId}:{$this->catalogableType}:{$this->catalogableId}";
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addHours(12);
    }

    public function middleware(): array
    {
        return [
            new RateLimited('botisimo-background'),
            (new WithoutOverlapping($this->uniqueId()))->releaseAfter(15)->expireAfter(120),
        ];
    }

    public function handle(CatalogSynchronizer $synchronizer): void
    {
        $bot = ShopBot::find($this->shopBotId);

        if ($bot === null) {
            return;
        }

        try {
            $synchronizer->sync($bot, $this->catalogableType, $this->catalogableId);
        } catch (BotisimoRateLimitException $e) {
            $this->release($e->retryAfter);
        }
    }
}
