<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'order_id', 'user_id', 'amount', 'currency', 'status', 'reason',
        'phone_number', 'transaction_id', 'processed_by', 'admin_notes',
        'refunded_at', 'rejected_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'refunded_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
