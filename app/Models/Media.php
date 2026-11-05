<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Support\MediaUrl;
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
        return MediaUrl::from($this->url);
    }
    
    protected $fillable = [
        'url',
        'type',
        'is_principal',
        'product_id',
        'service_id'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
