<?php

namespace App\Console\Commands;

use App\Models\ShopBot;
use App\Notifications\AssistantSuspendedNotification;
use App\Services\Botisimo\AssistantProvisioner;
use App\Services\Botisimo\Exceptions\BotisimoException;
use App\Services\Botisimo\Exceptions\BotisimoRateLimitException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EnforceBotisimoSubscriptions extends Command
{
    protected $signature = 'botisimo:enforce-subscriptions';

    protected $description = 'Désactive les assistants dont l\'abonnement Standard a pris fin';

    public function handle(AssistantProvisioner $provisioner): int
    {
        $bots = ShopBot::with(['shop.activeSubscription', 'shop.user'])
            ->where('status', ShopBot::STATUS_ACTIVE)
            ->get()
            ->reject(fn (ShopBot $bot) => $bot->shop->hasAssistantAccess());

        foreach ($bots as $bot) {
            try {
                $provisioner->setActive($bot, false);
                $bot->shop->user?->notify(new AssistantSuspendedNotification($bot));
            } catch (BotisimoRateLimitException) {
                break; // Le reste sera traité au prochain passage.
            } catch (BotisimoException $e) {
                Log::warning('Désactivation du bot impossible', ['shop_bot_id' => $bot->id, 'status' => $e->status]);
            }
        }

        return self::SUCCESS;
    }
}
