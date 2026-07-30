<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Order extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_DELIVERED = 'delivered';

    const VENDOR_STATUS_PENDING = 'pending';
    const VENDOR_STATUS_ACCEPTED = 'accepted';
    const VENDOR_STATUS_PREPARING = 'preparing';
    const VENDOR_STATUS_SHIPPED = 'shipped';
    const VENDOR_STATUS_DELIVERED = 'delivered';

    protected $fillable = [
        'order_number',
        'user_id',
        'customer_first_name',
        'customer_last_name',
        'delivery_address',
        'phone_number',
        'total_products',
        'total_price',
        'status',
        'is_delivered',
        'is_paid',
        'payment_method',
        'vendor_status',
        'platform_fee',
        'vendor_amount',
        'vendor_accepted_at',
        'vendor_preparing_at',
        'vendor_shipped_at',
        'vendor_delivered_at',
        'escrow_released_at',
    ];

    protected $casts = [
        'is_delivered' => 'boolean',
        'is_paid' => 'boolean',
        'total_price' => 'decimal:2',
        'platform_fee' => 'integer',
        'vendor_amount' => 'integer',
        'vendor_accepted_at' => 'datetime',
        'vendor_preparing_at' => 'datetime',
        'vendor_shipped_at' => 'datetime',
        'vendor_delivered_at' => 'datetime',
        'escrow_released_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::saving(function ($order) {
            if ($order->isDirty('status')) {
                if ($order->status === self::STATUS_DELIVERED) {
                    $order->is_delivered = true;
                } else {
                    $order->is_delivered = false;
                }
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot('quantity', 'price');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)
            ->withPivot('quantity', 'price');
    }

    public function payment(): MorphOne
    {
        return $this->morphOne(Payment::class, 'payable');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }
}
