<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopBot extends Model
{
    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONNECTING = 'connecting';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_ERROR = 'error';

    protected $fillable = [
        'shop_id',
        'channel',
        'botisimo_bot_id',
        'status',
        'last_error',
        'ai_model',
        'ai_instructions',
        'additional_instructions',
        'ai_temperature',
        'tone',
        'language',
        'delivery_policy',
        'return_policy',
        'welcome_message_enabled',
        'welcome_message',
        'away_message_enabled',
        'away_message',
        'human_transfer_keywords',
        'business_address',
        'ai_share_location',
        'whatsapp_phone_number',
        'phone_number_id',
        'quality_rating',
        'token_expires_at',
        'token_expiry_notified_at',
        'webhook_secret',
        'catalog_ai_enabled',
        'monthly_credit_quota',
        'last_synced_at',
    ];

    protected $hidden = ['webhook_secret'];

    protected function casts(): array
    {
        return [
            'ai_temperature' => 'float',
            'welcome_message_enabled' => 'boolean',
            'away_message_enabled' => 'boolean',
            'human_transfer_keywords' => 'array',
            'ai_share_location' => 'boolean',
            'token_expires_at' => 'datetime',
            'token_expiry_notified_at' => 'datetime',
            'webhook_secret' => 'encrypted',
            'catalog_ai_enabled' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function isProvisioned(): bool
    {
        return filled($this->botisimo_bot_id) && filled($this->webhook_secret);
    }

    public function isConnected(): bool
    {
        return filled($this->phone_number_id);
    }

    /** Basée sur APP_URL : Botisimo l'appelle depuis l'extérieur, hors de toute requête. */
    public function webhookUrl(): string
    {
        return rtrim((string) config('app.url'), '/').'/webhooks/botisimo/'.$this->getKey();
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function catalogItems(): HasMany
    {
        return $this->hasMany(ShopBotProduct::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(ShopBotConversation::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(ShopBotTemplate::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(ShopBotCampaign::class);
    }

    public function webhookEvents(): HasMany
    {
        return $this->hasMany(ShopBotWebhookEvent::class);
    }
}
