<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Lien entre un produit ou service et son entrée dans le catalogue Botisimo. */
class ShopBotProduct extends Model
{
    protected $fillable = [
        'shop_bot_id',
        'catalogable_type',
        'catalogable_id',
        'botisimo_product_id',
        'is_ad',
        'sync_hash',
        'synced_at',
        'sync_error',
    ];

    protected function casts(): array
    {
        return [
            'is_ad' => 'boolean',
            'synced_at' => 'datetime',
        ];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(ShopBot::class, 'shop_bot_id');
    }

    public function catalogable(): MorphTo
    {
        return $this->morphTo();
    }
}
