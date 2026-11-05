<?php

namespace App\Http\Resources;

use App\Models\ShopBotConversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ShopBotConversation */
class ShopBotConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_name' => $this->client_name,
            'client_phone' => $this->client_phone,
            'status' => $this->status,
            'ai_enabled' => $this->ai_enabled,
            'preview' => $this->last_message_preview,
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'unread_count' => $this->unread_count,
            'can_send_free_message' => $this->canSendFreeMessage(),
        ];
    }
}
