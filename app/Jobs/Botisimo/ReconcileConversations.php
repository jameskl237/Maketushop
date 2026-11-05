<?php

namespace App\Jobs\Botisimo;

use App\Models\ShopBot;
use App\Services\Botisimo\Exceptions\BotisimoException;
use App\Services\Botisimo\Exceptions\BotisimoRateLimitException;
use App\Services\Botisimo\InboxStore;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

class ReconcileConversations implements ShouldQueue
{
    use Queueable;

    public int $maxExceptions = 3;

    public function __construct(public int $shopBotId) {}

    public function retryUntil(): DateTimeInterface
    {
        return now()->addMinutes(50);
    }

    public function middleware(): array
    {
        return [
            new RateLimited('botisimo-background'),
            (new WithoutOverlapping("conversations:{$this->shopBotId}"))->dontRelease()->expireAfter(300),
        ];
    }

    public function handle(InboxStore $inbox): void
    {
        $bot = ShopBot::find($this->shopBotId);

        if ($bot === null || blank($bot->botisimo_bot_id)) {
            return;
        }

        try {
            $inbox->reconcileConversations($bot);
        } catch (BotisimoRateLimitException $e) {
            $this->release($e->retryAfter);
        } catch (BotisimoException $e) {
            Log::warning('Réconciliation des conversations échouée', ['shop_bot_id' => $bot->id, 'status' => $e->status]);
        }
    }
}
