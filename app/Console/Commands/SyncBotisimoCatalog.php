<?php

namespace App\Console\Commands;

use App\Jobs\Botisimo\ReconcileShopCatalog;
use App\Models\ShopBot;
use Illuminate\Console\Command;

class SyncBotisimoCatalog extends Command
{
    protected $signature = 'botisimo:sync-catalog {shop? : Identifiant de la boutique, toutes par défaut}
                            {--prune : Supprime chez Botisimo les entrées inconnues localement}';

    protected $description = 'Réconcilie le catalogue Botisimo et renvoie ce qui a changé';

    public function handle(): int
    {
        $bots = ShopBot::whereNotNull('botisimo_bot_id')
            ->whereNotNull('webhook_secret')
            ->when($this->argument('shop'), fn ($query, $shop) => $query->where('shop_id', $shop))
            ->pluck('id');

        foreach ($bots as $botId) {
            ReconcileShopCatalog::dispatch($botId, (bool) $this->option('prune'));
        }

        $this->info("{$bots->count()} catalogue(s) mis en file.");

        return self::SUCCESS;
    }
}
