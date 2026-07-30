<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'shop_subscription_id', 'plan', 'amount',
        'transaction_id', 'payment_method', 'status',
        'provider_data', 'paid_at',
        'period_start', 'period_end',
    ];

    protected $casts = [
        'amount' => 'integer',
        'provider_data' => 'json',
        'paid_at' => 'datetime',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(ShopSubscription::class, 'shop_subscription_id');
    }

    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }
}
