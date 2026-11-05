<?php

namespace App\Jobs\Botisimo;

use App\Services\Botisimo\AssistantProvisioner;
use App\Services\Botisimo\Exceptions\BotisimoRateLimitException;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Supprime le bot d'une boutique disparue. Reçoit l'identifiant distant, car la
 * ligne locale n'existe déjà plus : sans ce job, le bot continuerait à répondre
 * aux clients sur le solde de la plateforme.
 */
class DeleteRemoteBot implements ShouldQueue
{
    use Queueable;

    public int $maxExceptions = 5;

    public int $backoff = 300;

    public function __construct(public string $botisimoBotId)
    {
        $this->afterCommit();
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addDay();
    }

    public function handle(AssistantProvisioner $provisioner): void
    {
        try {
            $provisioner->removeRemote($this->botisimoBotId);
        } catch (BotisimoRateLimitException $e) {
            $this->release($e->retryAfter);
        }
    }

    public function failed(Throwable $e): void
    {
        Log::error('Bot Botisimo orphelin : suppression manuelle requise', [
            'botisimo_bot_id' => $this->botisimoBotId,
            'exception' => $e::class,
        ]);
    }
}
