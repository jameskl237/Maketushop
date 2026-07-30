<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopSubscription extends Model
{
    use HasFactory;

    protected $appends = ['is_active'];

    public const PLAN_FREE = 'free';
    public const PLAN_STANDARD = 'standard';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';

    public const PRICES = [
        self::PLAN_FREE => 0,
        self::PLAN_STANDARD => 1000,
    ];

    public const DURATION_DAYS = [
        self::PLAN_FREE => 30,
        self::PLAN_STANDARD => 30,
    ];

    protected $fillable = [
        'shop_id',
        'plan',
        'status',
        'amount',
        'transaction_id',
        'payment_method',
        'starts_at',
        'ends_at',
        'cancelled_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->ends_at?->isFuture();
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->isActive();
    }

    public function isFree(): bool
    {
        return $this->plan === self::PLAN_FREE;
    }

    public function isStandard(): bool
    {
        return $this->plan === self::PLAN_STANDARD;
    }

    public static function priceFor(string $plan): int
    {
        return self::PRICES[$plan] ?? 0;
    }

    public static function durationFor(string $plan): int
    {
        return self::DURATION_DAYS[$plan] ?? 30;
    }

    public static function plans(): array
    {
        return [
            self::PLAN_FREE => [
                'key' => self::PLAN_FREE,
                'name' => 'Gratuit',
                'price' => 0,
                'duration_days' => 30,
                'features' => [
                    '1 boutique',
                    'Jusqu\'à 5 produits',
                    'Support communauté',
                ],
            ],
            self::PLAN_STANDARD => [
                'key' => self::PLAN_STANDARD,
                'name' => 'Standard',
                'price' => 1000,
                'duration_days' => 30,
                'features' => [
                    'Boutiques illimitées',
                    'Produits illimités',
                    'Services illimités',
                    'Statistiques avancées',
                    'Support prioritaire',
                    'Sans commission',
                ],
            ],
        ];
    }
}
