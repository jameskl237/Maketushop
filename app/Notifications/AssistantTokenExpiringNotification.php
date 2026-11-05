<?php

namespace App\Notifications;

use App\Models\ShopBot;

/** La connexion WhatsApp du vendeur arrive à expiration. */
class AssistantTokenExpiringNotification extends BaseSiteNotification
{
    public function __construct(private ShopBot $bot) {}

    protected function payload(): array
    {
        return [
            'type' => 'assistant.token_expiring',
            'title' => 'Connexion WhatsApp bientôt expirée',
            'message' => sprintf(
                'La connexion WhatsApp de votre assistant expire le %s. Reconnectez votre numéro pour éviter une interruption.',
                $this->bot->token_expires_at->format('d/m/Y')
            ),
            'url' => route('backoffice.supplier.assistant.show', $this->bot->shop_id),
            'icon' => 'warning',
        ];
    }
}
