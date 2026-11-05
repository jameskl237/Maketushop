<?php

namespace App\Observers;

use App\Models\Media;
use App\Services\Botisimo\CatalogSynchronizer;

/** La photo principale fait partie de l'entrée du catalogue. */
class MediaObserver
{
    public function saved(Media $media): void
    {
        $this->queueParent($media);
    }

    public function deleted(Media $media): void
    {
        $this->queueParent($media);
    }

    private function queueParent(Media $media): void
    {
        $parent = $media->product ?? $media->service;

        if ($parent !== null) {
            CatalogSynchronizer::queue($parent, $parent->shop_id);
        }
    }
}
