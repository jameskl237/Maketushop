<?php

namespace App\Http\Resources;

use App\Models\ShopBotCampaign;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ShopBotCampaign */
class ShopBotCampaignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'template_name' => $this->template_name,
            'status' => $this->status,
            'error' => $this->error,
            'recipients_count' => $this->recipients_count,
            'sent' => $this->sent,
            'failed' => $this->failed,
            'credits_used' => $this->credits_used,
            'tags' => $this->payload['tags'] ?? [],
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
