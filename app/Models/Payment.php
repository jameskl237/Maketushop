<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'payable_type', 'payable_id',
        'provider', 'transaction_id', 'reference',
        'amount', 'currency', 'status',
        'payment_method', 'provider_data', 'paid_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'provider_data' => 'json',
        'paid_at' => 'datetime',
    ];

    public function payable()
    {
        return $this->morphTo();
    }

    public function cinetpayTransaction()
    {
        return $this->hasOne(CinetpayTransaction::class, 'transaction_id', 'transaction_id');
    }

    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
