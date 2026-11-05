<?php

namespace App\Observers;

use App\Jobs\Botisimo\DeleteRemoteBot;
use App\Models\Shop;
use App\Models\ShopBot;
use App\Models\User;

/** Les suppressions locales partent en cascade SQL : les bots distants sont à retirer ici. */
class ShopBotOwnerObserver
{
    public function deleting(Shop|User $owner): void
    {
        $shopIds = $owner instanceof Shop ? [$owner->id] : $owner->shops()->pluck('id');

        ShopBot::whereIn('shop_id', $shopIds)
            ->whereNotNull('botisimo_bot_id')
            ->pluck('botisimo_bot_id')
            ->each(fn ($id) => DeleteRemoteBot::dispatch($id));
    }
}
