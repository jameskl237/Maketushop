<?php

namespace App\Models;

use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Shop extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'city',
        'district',
        'phone',
        'logo',
        'banner',
        'user_id',
    ];

    protected $appends = ['logo_url', 'banner_url'];

    public function getLogoUrlAttribute(): ?string
    {
        return MediaUrl::from($this->logo);
    }

    public function getBannerUrlAttribute(): ?string
    {
        return MediaUrl::from($this->banner);
    }

    // Une boutique appartient à un utilisateur (supplier)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(ShopSubscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(ShopSubscription::class)
            ->where('status', ShopSubscription::STATUS_ACTIVE)
            ->where('ends_at', '>=', now())
            ->latestOfMany();
    }

    /** Les crédits sont payés par la plateforme : l'assistant est réservé au plan Standard. */
    public function hasAssistantAccess(): bool
    {
        return (bool) $this->activeSubscription?->isStandard();
    }

    /** Assistant IA WhatsApp de la boutique. */
    public function bot(): HasOne
    {
        return $this->hasOne(ShopBot::class)->where('channel', ShopBot::CHANNEL_WHATSAPP);
    }

    public function ratings(): MorphMany
    {
        return $this->morphMany(Rating::class, 'rateable');
    }

    public function getAverageRatingAttribute(): float
    {
        return round($this->ratings()->avg('score') ?? 0, 1);
    }

    public function getRatingsCountAttribute(): int
    {
        return $this->ratings()->count();
    }
}
