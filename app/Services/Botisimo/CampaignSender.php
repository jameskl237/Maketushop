<?php

namespace App\Services\Botisimo;

use App\Models\ShopBot;
use App\Models\ShopBotCampaign;
use App\Models\ShopBotTemplate;
use App\Services\Botisimo\Exceptions\BotisimoException;
use App\Services\Botisimo\Exceptions\BotisimoQuotaExceededException;
use App\Services\Botisimo\Exceptions\BotisimoRateLimitException;
use Illuminate\Support\Facades\Cache;

/**
 * Envoi d'une campagne par lots. La position est enregistrée après chaque lot :
 * une reprise après un rate limit ne renvoie jamais un lot déjà parti. Toute
 * autre erreur arrête la campagne, car rejouer un broadcast facturerait et
 * dérangerait deux fois les mêmes clients.
 */
class CampaignSender
{
    private const BATCH_SIZE = 1000;

    private const REFRESH_INTERVAL_SECONDS = 60;

    public function __construct(
        private BotisimoClient $client,
        private ClientDirectory $clients,
        private CreditLedger $credits,
    ) {}

    /** @return array{recipients:int, credits:int, remaining:int, exceeds_quota:bool} */
    public function estimate(ShopBot $bot, array $tags): array
    {
        $remaining = $this->credits->remaining($bot->shop);
        $recipients = count($this->clients->audience($bot, $tags, $remaining));
        $credits = $recipients * (int) config('botisimo.credits.marketing_cost');

        return [
            'recipients' => $recipients,
            'credits' => $credits,
            'remaining' => $remaining,
            'exceeds_quota' => $credits > $remaining,
        ];
    }

    /** Le verrou passe par la base : deux workers ne peuvent pas lancer la même campagne. */
    public function claim(ShopBotCampaign $campaign): bool
    {
        return ShopBotCampaign::whereKey($campaign->id)
            ->where('status', ShopBotCampaign::STATUS_SCHEDULED)
            ->update(['status' => ShopBotCampaign::STATUS_SENDING]) === 1;
    }

    /** @throws BotisimoRateLimitException pour que le job se relâche */
    public function send(ShopBotCampaign $campaign): void
    {
        try {
            $this->prepare($campaign);
            $this->sendBatches($campaign);
        } catch (BotisimoRateLimitException $e) {
            throw $e;
        } catch (BotisimoQuotaExceededException) {
            $this->fail($campaign, 'Plafond quotidien WhatsApp atteint. Relancez une campagne plus tard.');
        } catch (BotisimoException $e) {
            $this->fail($campaign, $e->userMessage());
        }
    }

    private function prepare(ShopBotCampaign $campaign): void
    {
        if (isset($campaign->payload['recipients'])) {
            return;
        }

        $bot = $campaign->bot;
        $shop = $bot->shop;
        $template = ShopBotTemplate::find($campaign->payload['template_id'] ?? null);

        if (! $shop->hasAssistantAccess() || $bot->status !== ShopBot::STATUS_ACTIVE) {
            throw new CampaignRejected('L\'assistant de cette boutique n\'est plus actif.');
        }

        if ($template === null || $template->shop_bot_id !== $bot->id || $template->status !== ShopBotTemplate::STATUS_APPROVED) {
            throw new CampaignRejected('Le modèle de cette campagne n\'est plus approuvé.');
        }

        $remaining = $this->credits->remaining($shop);
        $recipients = $this->clients->audience($bot, $campaign->payload['tags'] ?? [], $remaining);

        if ($recipients === []) {
            throw new CampaignRejected('Aucun destinataire joignable pour cette campagne.');
        }

        // Tout ou rien : ne pas envoyer la moitié d'une campagne faute de quota.
        if (count($recipients) * (int) config('botisimo.credits.marketing_cost') > $remaining) {
            throw new CampaignRejected('Le quota mensuel de crédits ne couvre pas cette campagne.');
        }

        $campaign->update([
            'recipients_count' => count($recipients),
            'payload' => ['recipients' => $recipients, 'cursor' => 0] + $campaign->payload,
        ]);
    }

    private function sendBatches(ShopBotCampaign $campaign): void
    {
        $payload = $campaign->payload;
        $bot = $campaign->bot;
        $shop = $bot->shop;
        $cost = (int) config('botisimo.credits.marketing_cost');

        while ($payload['cursor'] < count($payload['recipients'])) {
            $batch = array_slice($payload['recipients'], $payload['cursor'], self::BATCH_SIZE);

            $response = $this->credits->spend(
                $shop,
                'broadcast',
                count($batch) * $cost,
                "campaign:{$campaign->id}",
                fn () => $this->client->broadcast([
                    'bot_id' => $bot->botisimo_bot_id,
                    'template_name' => $campaign->template_name,
                    'recipients' => $batch,
                    'language' => $payload['language'],
                    'parameters' => $payload['parameters'] ?? [],
                ]),
            );

            $payload['cursor'] += count($batch);
            $failed = (int) (BotisimoPayload::get($response, 'failed', 'failed_count') ?? 0);

            $campaign->update([
                'botisimo_campaign_id' => BotisimoPayload::get($response, 'campaign_id', 'id') ?? $campaign->botisimo_campaign_id,
                'sent' => $campaign->sent + count($batch) - $failed,
                'failed' => $campaign->failed + $failed,
                'credits_used' => $campaign->credits_used + count($batch) * $cost,
                'payload' => $payload,
            ]);
        }

        $this->finish($campaign, ShopBotCampaign::STATUS_SENT);
    }

    public function fail(ShopBotCampaign $campaign, string $reason): void
    {
        $unsent = max(0, $campaign->recipients_count - $campaign->sent - $campaign->failed);

        $campaign->update(['failed' => $campaign->failed + $unsent]);
        $this->finish($campaign, ShopBotCampaign::STATUS_FAILED, $reason);
    }

    /** La liste des numéros n'est gardée que le temps de l'envoi. */
    private function finish(ShopBotCampaign $campaign, string $status, ?string $error = null): void
    {
        $payload = $campaign->payload;
        unset($payload['recipients'], $payload['cursor']);

        $campaign->update(['status' => $status, 'error' => $error, 'payload' => $payload]);
    }

    public function refreshHistory(ShopBot $bot): void
    {
        if (! Cache::add("botisimo:campaigns:{$bot->id}", 1, self::REFRESH_INTERVAL_SECONDS)) {
            return;
        }

        try {
            $response = $this->client->listCampaigns(['bot_id' => $bot->botisimo_bot_id, 'limit' => 100]);
        } catch (BotisimoException) {
            return;
        }

        foreach (BotisimoPayload::list($response, 'campaigns', 'items', 'data') ?? [] as $row) {
            $sent = is_array($row) ? BotisimoPayload::get($row, 'sent', 'sent_count') : null;

            if (! is_numeric($sent) || ! isset($row['id'])) {
                continue;
            }

            $bot->campaigns()->where('botisimo_campaign_id', (string) $row['id'])->update([
                'sent' => (int) $sent,
                'failed' => (int) (BotisimoPayload::get($row, 'failed', 'failed_count') ?? 0),
            ]);
        }
    }
}
