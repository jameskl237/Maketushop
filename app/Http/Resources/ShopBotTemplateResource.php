<?php

namespace App\Http\Resources;

use App\Models\ShopBotTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ShopBotTemplate */
class ShopBotTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'language' => $this->language,
            'status' => $this->status,
            'body' => $this->body(),
            'variable_count' => $this->variableCount(),
        ];
    }
}
