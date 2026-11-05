<?php

namespace App\Services\Botisimo;

use App\Models\Product;
use App\Models\Service;
use App\Support\MediaUrl;
use Illuminate\Support\Str;

/** Traduit un produit ou un service Maketu Shop en entrée du catalogue Botisimo. */
class CatalogMapper
{
    public function map(Product|Service $model, bool $isAd): array
    {
        $isProduct = $model instanceof Product;

        return array_filter([
            'name' => $isProduct ? $model->name : $model->title,
            'description' => $this->plainText($model->long_description ?: $model->description),
            // `price` est le prix payé au checkout ; `promotion_price` est l'ancien prix barré.
            'price' => is_numeric($model->price) ? (float) $model->price : null,
            'currency' => config('payments.currency', 'XAF'),
            'category' => $model->category?->name,
            'type' => $isProduct ? 'product' : 'service',
            'in_stock' => $isProduct ? ($model->in_stock && $model->quantity > 0) : true,
            'active' => $isProduct ? true : $model->is_active,
            'image_url' => $this->imageUrl($model),
            'payment_link' => MediaUrl::absolute(
                route($isProduct ? 'products.show' : 'services.show', $model, false)
            ),
            'is_ad' => $isAd,
        ], fn ($value) => $value !== null);
    }

    private function plainText(?string $html): ?string
    {
        $text = Str::squish(html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5));

        return $text === '' ? null : Str::limit($text, 2000);
    }

    private function imageUrl(Product|Service $model): ?string
    {
        $images = $model->medias->where('type', 'image');
        $image = $images->firstWhere('is_principal', true) ?? $images->first();

        return MediaUrl::absolute($image?->url);
    }
}
