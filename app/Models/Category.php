<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'image',
        'description'
    ];

    protected static function booted(): void
    {
        // Le menu catégories de l'en-tête est mis en cache (HandleInertiaRequests).
        $forget = fn () => Cache::forget('nav_categories');
        static::saved($forget);
        static::deleted($forget);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
}
