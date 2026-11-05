<?php

namespace App\Services\Botisimo;

use App\Models\Shop;
use App\Models\ShopBotCreditUsage;
use App\Services\Botisimo\Exceptions\BotisimoCreditException;
use App\Services\Botisimo\Exceptions\BotisimoException;
use App\Services\Botisimo\Exceptions\BotisimoRateLimitException;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

/**
 * Le solde Botisimo est commun à toutes les boutiques : chaque dépense passe ici
 * pour être plafonnée par boutique et tracée. Le verrou empêche deux envois
 * simultanés de dépasser ensemble le quota.
 */
class CreditLedger
{
    private const BALANCE_CACHE_SECONDS = 60;

    public function __construct(private BotisimoClient $client) {}

    public function quota(Shop $shop): int
    {
        return (int) ($shop->bot?->monthly_credit_quota ?? config('botisimo.credits.monthly_quota'));
    }

    public function usedThisMonth(Shop $shop): int
    {
        return (int) ShopBotCreditUsage::where('shop_id', $shop->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('credits');
    }

    public function remaining(Shop $shop): int
    {
        return max(0, $this->quota($shop) - $this->usedThisMonth($shop));
    }

    /**
     * @template T
     *
     * @param  callable(): T  $send
     * @return T
     */
    public function spend(Shop $shop, string $action, int $credits, ?string $reference, callable $send): mixed
    {
        try {
            return Cache::lock("botisimo:credits:{$shop->id}", 60)->block(10, function () use ($shop, $action, $credits, $reference, $send) {
                $this->ensureAvailable($shop, $credits);

                $result = $send();

                ShopBotCreditUsage::create([
                    'shop_id' => $shop->id,
                    'action' => $action,
                    'credits' => $credits,
                    'reference' => $reference,
                ]);

                return $result;
            });
        } catch (LockTimeoutException) {
            throw new BotisimoRateLimitException('Un autre envoi de cette boutique est en cours.', null);
        }
    }

    private function ensureAvailable(Shop $shop, int $credits): void
    {
        if ($this->remaining($shop) < $credits) {
            throw new BotisimoCreditException('Le quota mensuel de crédits de cette boutique est atteint.');
        }

        $balance = $this->globalBalance();

        if ($balance !== null && $balance < $credits) {
            throw new BotisimoCreditException('Le service est momentanément indisponible. Réessayez plus tard.');
        }
    }

    /** null si le solde est illisible : Botisimo refusera lui-même un envoi non couvert. */
    public function globalBalance(): ?int
    {
        $balance = Cache::remember('botisimo:balance', self::BALANCE_CACHE_SECONDS, function () {
            try {
                return BotisimoPayload::get($this->client->creditBalance(), 'balance', 'credits');
            } catch (BotisimoException) {
                return null;
            }
        });

        return is_numeric($balance) ? (int) $balance : null;
    }
}
