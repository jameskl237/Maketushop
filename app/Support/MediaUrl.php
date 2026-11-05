<?php

namespace App\Support;

/**
 * URL publique d'un fichier image, quel que soit l'environnement.
 *
 * Les fichiers envoyés (produits, logos, bannières…) sont stockés sur le disque
 * « public » (storage/app/public) et servis sous /storage/…
 *
 * On renvoie volontairement une URL RELATIVE à la racine du site (« /storage/… ») :
 * - elle ne dépend pas d'APP_URL (souvent http://localhost ou en http en prod) ;
 * - pas de contenu mixte http/https bloqué par le navigateur ;
 * - elle fonctionne avec ou sans le lien public/storage (cf. route media.public).
 */
class MediaUrl
{
    public static function from(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        // URL absolue vers NOTRE /storage enregistrée avec un ancien APP_URL
        // (http://localhost/storage/…, http au lieu de https…) : on garde le chemin.
        if (preg_match('#^(?:https?:)?//([^/]+)(/storage/.+)$#i', $path, $m) && self::isOwnHost($m[1])) {
            return $m[2];
        }

        // URL absolue externe (CDN, image distante) ou protocole relatif.
        if (preg_match('#^(https?:)?//#i', $path) || str_starts_with($path, 'data:')) {
            return $path;
        }

        // Déjà un chemin absolu du site (/images/…, /storage/…).
        if (str_starts_with($path, '/')) {
            return $path;
        }

        // Fichiers livrés avec l'application dans public/images.
        if (str_starts_with($path, 'images/')) {
            return '/' . $path;
        }

        // Chemin du disque public, éventuellement préfixé par erreur.
        $path = preg_replace('#^(public/|storage/)+#', '', $path);

        return '/storage/' . ltrim($path, '/');
    }

    /** URL complète pour un service externe, qui ne peut pas résoudre un chemin relatif. */
    public static function absolute(?string $path): ?string
    {
        $url = self::from($path);

        return match (true) {
            $url === null, str_starts_with($url, 'data:') => null,
            str_starts_with($url, '//') => 'https:'.$url,
            str_starts_with($url, '/') => rtrim((string) config('app.url'), '/').$url,
            default => $url,
        };
    }

    private static function isOwnHost(string $host): bool
    {
        $host = strtolower(preg_replace('#:\d+$#', '', $host));
        $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $requestHost = app()->bound('request') ? strtolower((string) request()->getHost()) : '';

        return in_array($host, array_filter(['localhost', '127.0.0.1', $appHost, $requestHost]), true);
    }
}
