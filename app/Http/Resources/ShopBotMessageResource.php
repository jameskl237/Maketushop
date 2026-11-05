<?php

namespace App\Http\Resources;

use App\Models\ShopBotMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ShopBotMessage */
class ShopBotMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'direction' => $this->direction,
            'type' => $this->type,
            'body' => $this->body,
            'status' => $this->status,
            'sent_at' => $this->sent_at?->toIso8601String(),
        ];
    }
}
