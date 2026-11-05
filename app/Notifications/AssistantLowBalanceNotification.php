<?php

namespace App\Notifications;

/** Le solde Botisimo commun à toutes les boutiques passe sous le seuil d'alerte. */
class AssistantLowBalanceNotification extends BaseSiteNotification
{
    public function __construct(private int $balance) {}

    protected function payload(): array
    {
        return [
            'type' => 'assistant.low_balance',
            'title' => 'Solde Botisimo bas',
            'message' => sprintf('Il reste %s crédits pour toutes les boutiques.', number_format($this->balance, 0, ',', ' ')),
            'url' => route('backoffice.admin.assistant.index'),
            'icon' => 'warning',
        ];
    }
}
