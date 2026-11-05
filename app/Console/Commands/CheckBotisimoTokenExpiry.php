<?php

namespace App\Console\Commands;

use App\Models\ShopBot;
use App\Notifications\AssistantTokenExpiringNotification;
use Illuminate\Console\Command;

class CheckBotisimoTokenExpiry extends Command
{
    protected $signature = 'botisimo:check-token-expiry';

    protected $description = 'Prévient les vendeurs dont la connexion WhatsApp expire bientôt';

    public function handle(): int
    {
        $limit = now()->addDays((int) config('botisimo.token_expiry_warning_days'));

        ShopBot::with('shop.user')
            ->whereNotNull('token_expires_at')
            ->where('token_expires_at', '<=', $limit)
            ->whereNull('token_expiry_notified_at')
            ->whereIn('status', [ShopBot::STATUS_ACTIVE, ShopBot::STATUS_INACTIVE])
            ->eachById(function (ShopBot $bot) {
                $bot->shop?->user?->notify(new AssistantTokenExpiringNotification($bot));
                $bot->update(['token_expiry_notified_at' => now()]);
            });

        return self::SUCCESS;
    }
}
