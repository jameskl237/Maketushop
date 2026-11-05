<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopBotCampaign extends Model
{
    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_SENDING = 'sending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'shop_bot_id',
        'channel',
        'botisimo_campaign_id',
        'template_name',
        'recipients_count',
        'sent',
        'failed',
        'credits_used',
        'status',
        'error',
        'payload',
        'scheduled_at',
    ];

    protected $hidden = ['payload'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'scheduled_at' => 'datetime',
        ];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(ShopBot::class, 'shop_bot_id');
    }
}
