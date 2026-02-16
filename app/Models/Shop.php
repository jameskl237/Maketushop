<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Shop extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'city',
        'district',
        'phone',
        'logo',
        'user_id',
    ];

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset(Storage::url($this->logo)) : null;
    }

    // Une boutique appartient à un utilisateur (supplier)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Une boutique a plusieurs produits
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
