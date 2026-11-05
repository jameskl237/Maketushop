<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\Service;
use App\Services\Botisimo\CatalogSynchronizer;

class CatalogObserver
{
    public function saved(Product|Service $model): void
    {
        CatalogSynchronizer::queue($model, $model->shop_id);

        // Passé à une autre boutique : il doit quitter le catalogue de l'ancienne.
        if ($model->wasChanged('shop_id')) {
            CatalogSynchronizer::queue($model, $model->getOriginal('shop_id'));
        }
    }

    public function deleted(Product|Service $model): void
    {
        CatalogSynchronizer::queue($model, $model->shop_id);
    }
}
