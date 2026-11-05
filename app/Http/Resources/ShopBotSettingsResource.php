<?php

namespace App\Http\Resources;

use App\Models\ShopBot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ShopBot */
class ShopBotSettingsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'status' => $this->status,
            'connected' => $this->isConnected(),
            'ai_model' => $this->ai_model,
            'additional_instructions' => $this->additional_instructions,
            'ai_temperature' => $this->ai_temperature,
            'tone' => $this->tone,
            'language' => $this->language,
            'delivery_policy' => $this->delivery_policy,
            'return_policy' => $this->return_policy,
            'welcome_message_enabled' => $this->welcome_message_enabled,
            'welcome_message' => $this->welcome_message,
            'away_message_enabled' => $this->away_message_enabled,
            'away_message' => $this->away_message,
            'human_transfer_keywords' => $this->human_transfer_keywords ?? [],
            'business_address' => $this->business_address,
            'ai_share_location' => $this->ai_share_location,
        ];
    }
}
