<?php

namespace App\Notifications;

use App\Models\ShopBot;
use App\Models\ShopBotConversation;
use Illuminate\Notifications\Messages\MailMessage;

/** Un client a utilisé un mot-clé de transfert : le vendeur doit reprendre la main. */
class AssistantHumanTransferNotification extends BaseSiteNotification
{
    public function __construct(
        private ShopBot $bot,
        private ShopBotConversation $conversation,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Un client demande un conseiller')
            ->line($this->payload()['message'])
            ->action('Ouvrir la conversation', $this->url());
    }

    protected function payload(): array
    {
        $client = $this->conversation->client_name ?: $this->conversation->client_phone;

        return [
            'type' => 'assistant.human_transfer',
            'title' => 'Un client demande un conseiller',
            'message' => "{$client} a besoin d'une réponse humaine sur WhatsApp.",
            'url' => $this->url(),
            'icon' => 'warning',
        ];
    }

    private function url(): string
    {
        return route('backoffice.supplier.assistant.inbox.show', [$this->bot->shop_id, $this->conversation->id]);
    }
}
