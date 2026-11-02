<?php

namespace App\Console\Commands;

use App\Services\CinetPay\CinetPayService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Diagnostic de la configuration CinetPay en production, sans afficher de secret :
 *   php artisan cinetpay:check
 */
class CinetPayCheck extends Command
{
    protected $signature = 'cinetpay:check';

    protected $description = 'Vérifie la configuration CinetPay (clés, environnement, URLs, base de données) et teste l\'authentification';

    public function handle(CinetPayService $cinetpay): int
    {
        $ok = true;
        $key = (string) config('services.cinetpay.api_key');

        $this->line('Environnement CinetPay : ' . ($cinetpay->isSandbox() ? 'SANDBOX' : 'PRODUCTION') . ' (' . $cinetpay->baseUrl() . ')');
        $this->line('Pays / devise         : ' . $cinetpay->country() . ' / ' . $cinetpay->currency());

        $checks = [
            'CINETPAY_API_KEY renseignée' => filled($key),
            'CINETPAY_API_PASSWORD renseigné' => filled(config('services.cinetpay.api_password')),
            'Préfixe de clé cohérent avec l\'URL' => !str_starts_with($key, 'sk_test_') && !str_starts_with($key, 'sk_live_')
                || (str_starts_with($key, 'sk_test_') === $cinetpay->isSandbox()),
            'Pays = CM et devise = XAF' => $cinetpay->country() === 'CM' && $cinetpay->currency() === 'XAF',
            'APP_URL en https' => str_starts_with((string) config('app.url'), 'https://'),
            'URL du webhook en https' => str_starts_with(route('cinetpay.webhook'), 'https://'),
            'Colonne cinetpay_transactions.notify_token présente' => $this->hasNotifyTokenColumn(),
        ];

        foreach ($checks as $label => $passed) {
            $this->line(($passed ? '<info>  OK </info> ' : '<error> KO </error> ') . $label);
            $ok = $ok && $passed;
        }

        $this->line('Webhook (notify_url)  : ' . route('cinetpay.webhook'));

        if (!$cinetpay->isConfigured()) {
            $this->error('Authentification non testée : clé ou mot de passe manquant.');
            return self::FAILURE;
        }

        try {
            $cinetpay->checkCredentials();
            $this->info('  OK  Authentification OAuth réussie.');
        } catch (\Throwable $e) {
            $this->error(' KO  Authentification refusée : ' . $e->getMessage());
            $ok = false;
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    private function hasNotifyTokenColumn(): bool
    {
        try {
            return Schema::hasColumn('cinetpay_transactions', 'notify_token');
        } catch (\Throwable $e) {
            $this->warn('Base de données injoignable : ' . \Illuminate\Support\Str::before($e->getMessage(), ' (Connection'));
            return false;
        }
    }
}
