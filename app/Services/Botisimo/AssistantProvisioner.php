<?php

namespace App\Services\Botisimo;

use App\Models\Shop;
use App\Models\ShopBot;
use App\Services\Botisimo\Exceptions\BotisimoConfigurationException;
use App\Services\Botisimo\Exceptions\BotisimoConflictException;
use App\Services\Botisimo\Exceptions\BotisimoException;
use App\Services\Botisimo\Exceptions\BotisimoNotFoundException;
use App\Services\Channels\ChannelProvider;
use Illuminate\Support\Facades\Cache;

/**
 * Cycle d'activation d'un assistant. Chaque étape est enregistrée dès qu'elle
 * réussit : relancer l'activation reprend là où elle s'est arrêtée, sans créer
 * un second bot chez Botisimo.
 */
class AssistantProvisioner
{
    public function __construct(
        private BotisimoClient $client,
        private ChannelProvider $channel,
        private AssistantInstructions $instructions,
    ) {}

    public function provision(Shop $shop, array $settings): ShopBot
    {
        $bot = Cache::lock("botisimo:provision:{$shop->id}", 60)->get(
            fn () => $this->runProvisioning($shop, $settings)
        );

        if (! $bot instanceof ShopBot) {
            throw new BotisimoException('Activation déjà en cours pour cette boutique.');
        }

        return $bot;
    }

    private function runProvisioning(Shop $shop, array $settings): ShopBot
    {
        $bot = ShopBot::firstOrNew(['shop_id' => $shop->id, 'channel' => $this->channel->channel()]);

        if ($bot->exists && $bot->isProvisioned()) {
            return $bot;
        }

        $bot->fill($settings + [
            'ai_model' => config('botisimo.default_model'),
            'human_transfer_keywords' => config('botisimo.human_transfer_keywords'),
            'welcome_message' => "Bonjour et bienvenue chez {$shop->name} ! Comment puis-je vous aider ?",
        ]);
        $bot->setRelation('shop', $shop);
        $bot->save();

        if (! str_starts_with($bot->webhookUrl(), 'https://')) {
            throw new BotisimoConfigurationException('APP_URL doit être une URL HTTPS publique pour recevoir les webhooks Botisimo.');
        }

        $bot->ai_instructions = $this->instructions->for($bot);

        try {
            $this->createRemoteBot($bot, $shop);
            $this->client->updateBot($bot->botisimo_bot_id, $this->remoteSettings($bot));
            $this->registerWebhook($bot);
        } catch (BotisimoException $e) {
            $bot->update(['last_error' => $e->userMessage()]);

            throw $e;
        }

        $bot->update(['last_error' => null]);

        return $bot;
    }

    private function createRemoteBot(ShopBot $bot, Shop $shop): void
    {
        if (filled($bot->botisimo_bot_id)) {
            return;
        }

        $response = $this->client->createBot([
            'name' => $shop->name,
            'description' => $shop->description,
            'ai_model' => $bot->ai_model,
            'ai_instructions' => $bot->ai_instructions,
        ]);

        $id = BotisimoPayload::get($response, 'id', 'bot_id');

        if ($id === null) {
            throw new BotisimoException('Réponse de création de bot Botisimo sans identifiant.');
        }

        $bot->update(['botisimo_bot_id' => (string) $id]);
    }

    private function remoteSettings(ShopBot $bot): array
    {
        return array_filter([
            'ai_instructions' => $bot->ai_instructions,
            'ai_temperature' => $bot->ai_temperature,
            'welcome_message_enabled' => $bot->welcome_message_enabled,
            'welcome_message' => $bot->welcome_message,
            'away_message_enabled' => $bot->away_message_enabled,
            'away_message' => $bot->away_message,
            'enable_context_memory' => true,
            'context_message_limit' => config('botisimo.context_message_limit'),
            'enable_human_transfer' => true,
            'human_transfer_keywords' => $bot->human_transfer_keywords,
            'business_address' => $bot->business_address,
            'ai_share_location' => $bot->ai_share_location,
        ], fn ($value) => $value !== null);
    }

    private function registerWebhook(ShopBot $bot): void
    {
        $id = $bot->botisimo_bot_id;
        $events = config('botisimo.webhook_events');

        try {
            $response = $this->client->createWebhook($id, $bot->webhookUrl(), $events);
        } catch (BotisimoConflictException) {
            // Un webhook existe mais son secret, affiché une seule fois, a été perdu.
            $this->client->deleteWebhook($id);
            $response = $this->client->createWebhook($id, $bot->webhookUrl(), $events);
        }

        $secret = BotisimoPayload::get($response, 'secret');

        if (! is_string($secret)) {
            throw new BotisimoException('Réponse de création de webhook Botisimo sans secret.');
        }

        $bot->update(['webhook_secret' => $secret]);
    }

    /** Renvoie l'URL Meta à ouvrir : générée au clic, elle ne peut pas avoir expiré. */
    public function startConnection(ShopBot $bot, string $callbackUrl): string
    {
        $url = $this->channel->connectionUrl($bot, $callbackUrl);

        $bot->update(['status' => ShopBot::STATUS_CONNECTING, 'last_error' => null]);

        return $url;
    }

    public function failConnection(ShopBot $bot, string $reason): void
    {
        $bot->update(['status' => ShopBot::STATUS_ERROR, 'last_error' => $reason]);
    }

    /** Le numéro est enregistré avant les appels distants : leur échec ne le perd pas. */
    public function completeConnection(ShopBot $bot, ?string $phoneNumber = null, ?string $phoneNumberId = null): void
    {
        $bot->update(array_filter([
            'whatsapp_phone_number' => $phoneNumber,
            'phone_number_id' => $phoneNumberId,
        ]));

        try {
            $remote = $this->client->getBot($bot->botisimo_bot_id);

            $bot->fill(array_filter([
                'whatsapp_phone_number' => BotisimoPayload::get($remote, 'phone_number', 'whatsapp_phone_number'),
                'phone_number_id' => BotisimoPayload::get($remote, 'meta_phone_number_id', 'phone_number_id'),
                'quality_rating' => BotisimoPayload::get($remote, 'quality_rating'),
                'token_expires_at' => BotisimoPayload::date(BotisimoPayload::get($remote, 'token_expires_at')),
            ]))->save();

            $this->client->activateBot($bot->botisimo_bot_id);
        } catch (BotisimoException $e) {
            $this->failConnection($bot, $e->userMessage());

            throw $e;
        }

        $bot->update([
            'status' => ShopBot::STATUS_ACTIVE,
            'last_error' => null,
            'token_expiry_notified_at' => null,
        ]);
    }

    /** Enregistré localement seulement si Botisimo a accepté : les deux restent alignés. */
    public function updateSettings(ShopBot $bot, array $settings): void
    {
        $bot->fill($settings);
        $bot->ai_instructions = $this->instructions->for($bot);

        $this->client->updateBot($bot->botisimo_bot_id, [
            // Une chaîne vide efface la valeur ; une clé absente la laisserait en place.
            'welcome_message' => $bot->welcome_message ?? '',
            'away_message' => $bot->away_message ?? '',
            'business_address' => $bot->business_address ?? '',
        ] + $this->remoteSettings($bot));

        $bot->save();
    }

    public function setActive(ShopBot $bot, bool $active): void
    {
        if ($active) {
            $this->client->activateBot($bot->botisimo_bot_id);
        } else {
            $this->client->deactivateBot($bot->botisimo_bot_id);
        }

        $bot->update(['status' => $active ? ShopBot::STATUS_ACTIVE : ShopBot::STATUS_INACTIVE]);
    }

    /** Irréversible. La ligne locale disparaît seulement une fois le bot distant retiré. */
    public function destroy(ShopBot $bot): void
    {
        if (filled($bot->botisimo_bot_id)) {
            $this->removeRemote($bot->botisimo_bot_id);
        }

        $bot->delete();
    }

    public function removeRemote(string $botisimoBotId): void
    {
        try {
            $this->client->deactivateBot($botisimoBotId);
            $this->client->deleteBot($botisimoBotId);
        } catch (BotisimoNotFoundException) {
            // Déjà supprimé : le résultat voulu.
        }
    }
}
