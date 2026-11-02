<?php

namespace App\Support;

/**
 * Normalisation des numéros de téléphone au format international E.164 (+XXX…).
 *
 * Les numéros arrivent sous plusieurs formes selon l'écran d'origine :
 * "6XXXXXXXX" (saisie locale), "2376XXXXXXXX" (stocké en base sans +),
 * "+237 6XX XX XX XX", "002376XXXXXXXX". Tous doivent donner "+2376XXXXXXXX".
 */
class PhoneNumber
{
    /** Indicatif et longueur du numéro national, par pays CinetPay. */
    public const COUNTRIES = [
        'CM' => ['dial' => '237', 'length' => 9],
        'CI' => ['dial' => '225', 'length' => 10],
        'SN' => ['dial' => '221', 'length' => 9],
        'BF' => ['dial' => '226', 'length' => 8],
        'ML' => ['dial' => '223', 'length' => 8],
        'TG' => ['dial' => '228', 'length' => 8],
        'BJ' => ['dial' => '229', 'length' => 10],
        'NE' => ['dial' => '227', 'length' => 8],
        'GN' => ['dial' => '224', 'length' => 9],
        'CD' => ['dial' => '243', 'length' => 9],
    ];

    public static function defaultCountry(): string
    {
        return strtoupper((string) config('services.cinetpay.country', 'CM'));
    }

    public static function dialCode(?string $country = null): string
    {
        $country = strtoupper($country ?? self::defaultCountry());

        return self::COUNTRIES[$country]['dial'] ?? self::COUNTRIES['CM']['dial'];
    }

    /**
     * Retourne le numéro en E.164 (ex. "+237690000000"), ou '' si vide.
     * Un numéro sans indicatif reçoit celui du pays par défaut.
     */
    public static function toE164(?string $phone, ?string $country = null): string
    {
        $phone = trim((string) $phone);

        if ($phone === '') {
            return '';
        }

        $hasPlus = str_starts_with($phone, '+');
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        if ($hasPlus) {
            return '+' . $digits;
        }

        if (str_starts_with($digits, '00')) {
            return '+' . substr($digits, 2);
        }

        $country = strtoupper($country ?? self::defaultCountry());
        $dial = self::dialCode($country);
        $nationalLength = self::COUNTRIES[$country]['length'] ?? 9;

        // Déjà préfixé par l'indicatif (format stocké en base : "2376XXXXXXXX").
        if (str_starts_with($digits, $dial) && strlen($digits) >= strlen($dial) + $nationalLength) {
            return '+' . $digits;
        }

        // Trunk prefix national éventuel ("06XXXXXXXX").
        if (strlen($digits) > $nationalLength && str_starts_with($digits, '0')) {
            $digits = ltrim($digits, '0');
        }

        return '+' . $dial . $digits;
    }

    /**
     * Vérifie qu'un numéro E.164 est un mobile du pays du compte CinetPay :
     * un compte marchand camerounais ne peut débiter que des wallets camerounais.
     * Cameroun : 9 chiffres commençant par 6 (Orange, MTN, Nexttel, Camtel mobile).
     */
    public static function isValidMobile(string $e164, ?string $country = null): bool
    {
        $country = strtoupper($country ?? self::defaultCountry());
        $dial = self::dialCode($country);
        $length = self::COUNTRIES[$country]['length'] ?? 9;

        if (!preg_match('/^\+' . $dial . '\d{' . $length . '}$/', $e164)) {
            return false;
        }

        if ($country === 'CM') {
            return str_starts_with($e164, '+2376');
        }

        return true;
    }
}
