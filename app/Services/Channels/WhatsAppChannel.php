<?php

namespace App\Services\Channels;

use App\Models\ShopBot;
use App\Services\Botisimo\BotisimoClient;
use App\Services\Botisimo\BotisimoPayload;
use App\Services\Botisimo\Exceptions\BotisimoException;

class WhatsAppChannel implements ChannelProvider
{
    public function __construct(private BotisimoClient $client) {}

    public function channel(): string
    {
        return ShopBot::CHANNEL_WHATSAPP;
    }

    public function connectionUrl(ShopBot $bot, string $callbackUrl): string
    {
        $response = $this->client->connectBot($bot->botisimo_bot_id, $callbackUrl);
        $url = BotisimoPayload::get($response, 'redirect_url');

        if (! is_string($url)) {
            throw new BotisimoException('Réponse de connexion Botisimo sans redirect_url.');
        }

        return $url;
    }

    public function sendText(ShopBot $bot, string $to, string $message): array
    {
        return $this->client->sendMessage($bot->botisimo_bot_id, [
            'to' => ltrim($to, '+'), // E.164 sans le +
            'message' => $message,
            'type' => 'text',
        ]);
    }
}
