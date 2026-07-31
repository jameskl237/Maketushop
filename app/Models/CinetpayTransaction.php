<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CinetpayTransaction extends Model
{
    protected $fillable = [
        'transaction_id', 'site_id', 'country', 'amount', 'currency',
        'status', 'payment_method', 'cpm_trans_id',
        'notify_token', 'payment_token', 'payment_url',
        'customer_name', 'customer_email', 'customer_phone',
        'description', 'raw_request', 'raw_response', 'raw_webhook',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'raw_request' => 'array',
        'raw_response' => 'array',
        'raw_webhook' => 'array',
        'paid_at' => 'datetime',
    ];

    public function payment()
    {
        return $this->hasOne(Payment::class, 'transaction_id', 'transaction_id');
    }

    public function isSuccess(): bool
    {
        return $this->status === 'SUCCESS' || $this->status === 'VALIDATED';
    }
}
