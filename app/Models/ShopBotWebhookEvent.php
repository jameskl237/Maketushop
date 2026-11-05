<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopBotWebhookEvent extends Model
{
    use MassPrunable;

    protected $fillable = ['shop_bot_id', 'event', 'event_key', 'payload', 'processed_at', 'error'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    /** Les charges contiennent des numéros et des messages de clients. */
    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays((int) config('botisimo.webhook_event_retention_days')));
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(ShopBot::class, 'shop_bot_id');
    }
}
