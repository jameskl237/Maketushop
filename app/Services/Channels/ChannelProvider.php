<?php

namespace App\Services\Channels;

use App\Models\ShopBot;

/**
 * Canal de messagerie d'un assistant. Seul WhatsApp existe aujourd'hui : l'API
 * Botisimo ne couvre ni Messenger, ni Instagram, ni TikTok.
 */
interface ChannelProvider
{
    /** Valeur de la colonne `channel` des tables shop_bot_*. */
    public function channel(): string;

    /** Démarre la connexion du compte et renvoie l'URL à ouvrir dans le navigateur. */
    public function connectionUrl(ShopBot $bot, string $callbackUrl): string;

    /** @return array<string, mixed> */
    public function sendText(ShopBot $bot, string $to, string $message): array;
}
