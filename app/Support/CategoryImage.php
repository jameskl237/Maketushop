<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Image d'une catégorie sans image enregistrée en base.
 *
 * 1. Fichier nommé comme la catégorie (slug) :
 *      public/images/categories/mode-et-vetements.(webp|jpg|jpeg|png|svg)
 *      ou public/images/mode-et-vetements.(…)
 * 2. Sinon, visuel thématique de public/images choisi d'après les mots du nom
 *    (« Mode et Vêtements » → mode.png, « Informatique et High-Tech » → electronique.png).
 */
class CategoryImage
{
    private const EXTENSIONS = ['webp', 'jpg', 'jpeg', 'png', 'svg'];

    /** Ordre important : le premier thème dont un mot-clé apparaît l'emporte. */
    private const THEMES = [
        'alimentation' => ['aliment', 'fruit', 'legume', 'boulang', 'patiss', 'viande', 'poisson', 'restaur', 'epicer', 'boisson', 'cuisine', 'food'],
        'mode' => ['mode', 'vetement', 'chaussure', 'habit', 'textile', 'pagne', 'fashion'],
        'beaute' => ['beaute', 'cosmet', 'sante', 'bien-etre', 'soin', 'parfum', 'coiff', 'beauty'],
        'electronique' => ['electro', 'informatique', 'high-tech', 'telephon', 'phone', 'ordinateur', 'tech', 'gadget'],
        'maison' => ['maison', 'deco', 'meuble', 'menag', 'bricol', 'quincaill', 'jardin', 'nettoy', 'home'],
        'sport' => ['sport', 'loisir', 'fitness', 'plein-air'],
        'accessoire' => ['accessoire', 'auto', 'moto', 'bebe', 'enfant', 'jouet', 'bijou', 'montre', 'sac'],
        'artisanat' => ['artisan', 'librairie', 'culture', 'livre', 'fourniture', 'scolaire', 'bureau', 'evenement', 'service', 'reparation', 'maintenance', 'professionnel'],
    ];

    /** @var array<string, string|null> */
    private static array $memo = [];

    public static function for(?string $slug, ?string $name): ?string
    {
        $slug = Str::slug((string) ($slug ?: $name));

        if ($slug === '') {
            return null;
        }

        return self::$memo[$slug] ??= self::resolve($slug);
    }

    private static function resolve(string $slug): ?string
    {
        foreach (['images/categories', 'images'] as $dir) {
            if ($file = self::find($dir, $slug)) {
                return $file;
            }
        }

        foreach (self::THEMES as $file => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($slug, $keyword) && ($found = self::find('images/categories', $file) ?? self::find('images', $file))) {
                    return $found;
                }
            }
        }

        return null;
    }

    private static function find(string $dir, string $basename): ?string
    {
        foreach (self::EXTENSIONS as $ext) {
            if (is_file(public_path("{$dir}/{$basename}.{$ext}"))) {
                return "/{$dir}/{$basename}.{$ext}";
            }
        }

        return null;
    }
}
