<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    use HasFactory;
    
    protected $table = 'medias'; 
     protected $appends = ['full_url'];

    public function getFullUrlAttribute()
    {
        if (!$this->url) return null;
        // External URLs (Picsum, etc.) returned as-is
        if (str_starts_with($this->url, 'http://') || str_starts_with($this->url, 'https://')) {
            return $this->url;
        }
        return asset(Storage::url($this->url));
    }
    
    protected $fillable = [
        'url',
        'type',
        'is_principal',
        'product_id'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
