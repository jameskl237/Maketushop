<?php

namespace App\Http\Resources;

use App\Models\Product;
use App\Models\ShopBotProduct;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ShopBotProduct */
class ShopBotProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $model = $this->catalogable;

        return [
            'id' => $this->id,
            'name' => $model instanceof Product ? $model->name : $model?->title,
            'type' => $this->catalogable_type,
            'is_ad' => $this->is_ad,
            'synced' => filled($this->botisimo_product_id) && $this->sync_error === null,
            'sync_error' => $this->sync_error,
        ];
    }
}
