<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $fillable = [
        'provider', 'event', 'transaction_id', 'status',
        'headers', 'payload', 'response', 'error', 'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'headers' => 'array',
            'payload' => 'array',
            'response' => 'array',
            'error' => 'array',
        ];
    }
}
