<?php

namespace App\Jobs\Botisimo;

use App\Models\ShopBotCampaign;
use App\Services\Botisimo\CampaignSender;
use App\Services\Botisimo\Exceptions\BotisimoRateLimitException;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

class SendCampaign implements ShouldQueue
{
    use Queueable;

    // Une exception inattendue arrête la campagne : aucune relance automatique d'un envoi de masse.
    public int $maxExceptions = 1;

    public function __construct(public int $campaignId) {}

    public function retryUntil(): DateTimeInterface
    {
        return now()->addHours(6);
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping("campaign:{$this->campaignId}"))->dontRelease()->expireAfter(600)];
    }

    public function handle(CampaignSender $sender): void
    {
        $campaign = ShopBotCampaign::find($this->campaignId);

        if ($campaign === null) {
            return;
        }

        // Seule une reprise après rate limit trouve la campagne déjà en cours.
        $isResume = $this->attempts() > 1 && $campaign->status === ShopBotCampaign::STATUS_SENDING;

        if (! $isResume && ! $sender->claim($campaign)) {
            return;
        }

        try {
            $sender->send($campaign->fresh());
        } catch (BotisimoRateLimitException $e) {
            $this->release($e->retryAfter);
        }
    }

    public function failed(Throwable $e): void
    {
        $campaign = ShopBotCampaign::find($this->campaignId);

        if ($campaign?->status === ShopBotCampaign::STATUS_SENDING) {
            app(CampaignSender::class)->fail($campaign, 'L\'envoi a été interrompu. Vérifiez l\'historique avant de relancer.');
        }
    }
}
