<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\AssistantLowBalanceNotification;
use App\Services\Botisimo\CreditLedger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class CheckBotisimoBalance extends Command
{
    private const ALERT_COOLDOWN_SECONDS = 86400;

    protected $signature = 'botisimo:check-balance';

    protected $description = 'Prévient les administrateurs quand le solde Botisimo passe sous le seuil';

    public function handle(CreditLedger $credits): int
    {
        $balance = $credits->globalBalance();

        if ($balance !== null
            && $balance < (int) config('botisimo.credits.low_balance_threshold')
            && Cache::add('botisimo:low-balance-alert', 1, self::ALERT_COOLDOWN_SECONDS)) {
            Notification::send(
                User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPERADMIN])->get(),
                new AssistantLowBalanceNotification($balance),
            );
        }

        return self::SUCCESS;
    }
}
