<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteRequest extends Model
{
    public const STATUS_PENDING = 'en_attente';
    public const STATUS_HANDLED = 'traite';

    protected $fillable = [
        'service_id',
        'user_id',
        'customer_name',
        'customer_phone',
        'budget',
        'message',
        'status',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
