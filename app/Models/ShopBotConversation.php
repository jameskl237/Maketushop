<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopBotConversation extends Model
{
    private const FREE_MESSAGE_WINDOW_HOURS = 24;

    protected $fillable = [
        'shop_bot_id',
        'channel',
        'botisimo_conversation_id',
        'client_name',
        'client_phone',
        'status',
        'ai_enabled',
        'last_message_preview',
        'last_message_at',
        'last_inbound_at',
        'unread_count',
    ];

    protected function casts(): array
    {
        return [
            'ai_enabled' => 'boolean',
            'last_message_at' => 'datetime',
            'last_inbound_at' => 'datetime',
        ];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(ShopBot::class, 'shop_bot_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ShopBotMessage::class);
    }

    /** Message libre autorisé 24 h après le dernier message du client ; template au-delà. */
    public function canSendFreeMessage(): bool
    {
        return $this->last_inbound_at !== null
            && $this->last_inbound_at->gt(now()->subHours(self::FREE_MESSAGE_WINDOW_HOURS));
    }
}
