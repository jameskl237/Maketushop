<?php

namespace App\Jobs\Botisimo;

use App\Models\ShopBotWebhookEvent;
use App\Services\Botisimo\InboxStore;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessBotisimoWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public int $eventId) {}

    public function handle(InboxStore $inbox): void
    {
        $event = ShopBotWebhookEvent::with('bot.shop.user')->find($this->eventId);

        if ($event === null || $event->processed_at !== null) {
            return;
        }

        match ($event->event) {
            'message.received' => $inbox->recordInbound($event->bot, $event->payload),
            'message.status' => $inbox->recordStatus($event->bot, $event->payload),
            default => Log::info('Événement Botisimo ignoré', ['event' => $event->event]),
        };

        $event->update(['processed_at' => now(), 'error' => null]);
    }

    public function failed(Throwable $e): void
    {
        ShopBotWebhookEvent::whereKey($this->eventId)->update(['error' => $e::class]);
    }
}
