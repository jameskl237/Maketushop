<?php

use App\Models\ShopBotWebhookEvent;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Relances et annulations automatiques des commandes non livrées.
// Nécessite le cron système : * * * * * php /chemin/artisan schedule:run
Schedule::command('orders:process-delays')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();

// Prévient les vendeurs dont la connexion WhatsApp expire bientôt.
Schedule::command('botisimo:check-token-expiry')
    ->daily()
    ->withoutOverlapping()
    ->onOneServer();

// Rattrape ce que les observers ne voient pas (suppressions en cascade SQL, renommage de catégorie).
Schedule::command('botisimo:sync-catalog')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onOneServer();

// Botisimo ne rejoue pas ses webhooks : on rattrape les conversations manquées.
Schedule::command('botisimo:sync-conversations')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();

// Supprime les charges de webhook, qui contiennent des données de clients.
Schedule::command('model:prune', ['--model' => [ShopBotWebhookEvent::class]])
    ->daily()
    ->onOneServer();

// Campagnes WhatsApp planifiées par les vendeurs.
Schedule::command('botisimo:dispatch-campaigns')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();

// L'assistant est réservé au plan Standard : un abonnement fini arrête la dépense de crédits.
Schedule::command('botisimo:enforce-subscriptions')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('botisimo:check-balance')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();
