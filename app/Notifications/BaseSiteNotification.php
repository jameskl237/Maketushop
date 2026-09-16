<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notification affichée dans la cloche du site (canal "database" uniquement).
 * Chaque sous-classe décrit son contenu via payload().
 */
abstract class BaseSiteNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload();
    }

    /**
     * @return array{type:string, title:string, message:string, url:?string, icon:string}
     */
    abstract protected function payload(): array;
}
