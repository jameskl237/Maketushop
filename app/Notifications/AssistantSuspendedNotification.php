<?php

namespace App\Notifications;

use App\Models\ShopBot;

/** L'abonnement Standard a pris fin : l'assistant ne répond plus aux clients. */
class AssistantSuspendedNotification extends BaseSiteNotification
{
    public function __construct(private ShopBot $bot) {}

    protected function payload(): array
    {
        return [
            'type' => 'assistant.suspended',
            'title' => 'Assistant IA désactivé',
            'message' => 'Votre abonnement Standard a pris fin : votre assistant WhatsApp ne répond plus. Renouvelez-le puis réactivez l\'assistant.',
            'url' => route('backoffice.supplier.assistant.settings.edit', $this->bot->shop_id),
            'icon' => 'warning',
        ];
    }
}
