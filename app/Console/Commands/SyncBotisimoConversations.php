<?php

namespace App\Console\Commands;

use App\Jobs\Botisimo\ReconcileConversations;
use App\Models\ShopBot;
use Illuminate\Console\Command;

class SyncBotisimoConversations extends Command
{
    protected $signature = 'botisimo:sync-conversations {shop? : Identifiant de la boutique, toutes par défaut}';

    protected $description = 'Récupère les conversations manquées (Botisimo ne rejoue pas ses webhooks)';

    public function handle(): int
    {
        $bots = ShopBot::where('status', ShopBot::STATUS_ACTIVE)
            ->when($this->argument('shop'), fn ($query, $shop) => $query->where('shop_id', $shop))
            ->pluck('id');

        foreach ($bots as $botId) {
            ReconcileConversations::dispatch($botId);
        }

        $this->info("{$bots->count()} boîte(s) de réception mise(s) en file.");

        return self::SUCCESS;
    }
}
