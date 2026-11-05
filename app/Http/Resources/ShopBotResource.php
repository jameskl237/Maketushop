<?php

namespace App\Http\Resources;

use App\Models\ShopBot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ShopBot */
class ShopBotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'provisioned' => $this->isProvisioned(),
            'connected' => $this->isConnected(),
            'last_error' => $this->last_error,
            'tone' => $this->tone,
            'language' => $this->language,
            'delivery_policy' => $this->delivery_policy,
            'return_policy' => $this->return_policy,
            'whatsapp_phone_number' => $this->whatsapp_phone_number,
            'quality_rating' => $this->quality_rating,
            'token_expires_at' => $this->token_expires_at?->toIso8601String(),
            'catalog_ai_enabled' => $this->catalog_ai_enabled,
        ];
    }
}
