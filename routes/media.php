<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
| Fichiers du disque « public » (photos produits, logos, bannières…).
|
| Normalement le serveur web les sert directement via le lien public/storage
| (php artisan storage:link) et cette route n'est jamais atteinte. Si le lien
| manque en production, elle prend le relais au lieu de renvoyer 403/404.
| Enregistrée hors du groupe « web » : pas de session ni de requête en base.
*/
Route::get('/storage/{path}', function (string $path) {
    $disk = Storage::disk('public');

    abort_if(str_contains($path, '..') || ! $disk->exists($path), 404);

    return response()->file($disk->path($path), [
        'Cache-Control' => 'public, max-age=2592000, immutable',
    ]);
})->where('path', '.*')->name('media.public');
