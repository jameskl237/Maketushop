<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Order extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_DELIVERED = 'delivered';

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
    ];

    protected $casts = [
        'is_delivered' => 'boolean',
        'is_paid' => 'boolean',
        'total_price' => 'decimal:2',
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
}
