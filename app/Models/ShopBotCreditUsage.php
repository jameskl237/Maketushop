<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopBotCreditUsage extends Model
{
    protected $fillable = ['shop_id', 'action', 'credits', 'reference'];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
