<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_SUPPLIER = 'supplier';
    public const ROLE_SUPERADMIN = 'superadmin';
    public const ROLE_USER = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
     protected $fillable = [
        'name',
        'username',
        'email',
        'google_id',
        'role',
        'is_vendeur',
        'is_prestataire',
        'phone',
        'address',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_vendeur' => 'boolean',
        'is_prestataire' => 'boolean',
    ];

    /**
     * Compte professionnel (vendeur et/ou prestataire).
     */
    public function isPro(): bool
    {
        return $this->role === self::ROLE_SUPPLIER;
    }

    public function isVendeur(): bool
    {
        return $this->isPro() && (bool) $this->is_vendeur;
    }

    public function isPrestataire(): bool
    {
        return $this->isPro() && (bool) $this->is_prestataire;
    }

    public function dashboardRouteName(): string
    {
        return match ($this->role) {
            self::ROLE_SUPERADMIN => 'backoffice.superadmin.dashboard',
            self::ROLE_ADMIN => 'backoffice.admin.dashboard',
            self::ROLE_SUPPLIER => 'backoffice.supplier.dashboard',
            default => 'user.dashboard',
        };
    }

    // 🏪 Un utilisateur peut avoir plusieurs boutiques
    public function shops()
    {
        return $this->hasMany(Shop::class);
    }

    // Abonnements boutique via ses boutiques
    public function shopSubscriptions()
    {
        return $this->hasManyThrough(ShopSubscription::class, Shop::class);
    }

    // 📦 Un utilisateur peut avoir plusieurs produits (qu’il a créés)
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    // 🛍️ Un utilisateur peut avoir plusieurs commandes
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // ❤️ Un utilisateur peut avoir plusieurs favoris (produits et services)
    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function hasFavorited(Model $model): bool
    {
        return $this->favorites()
            ->where('favoritable_type', $model->getMorphClass())
            ->where('favoritable_id', $model->getKey())
            ->exists();
    }

    /**
     * IDs des produits favoris (pour hydrater le frontend).
     *
     * @return array<int, int>
     */
    public function favoriteProductIds(): array
    {
        return $this->favorites()
            ->where('favoritable_type', Product::class)
            ->pluck('favoritable_id')
            ->all();
    }

    /**
     * IDs des services favoris (pour hydrater le frontend).
     *
     * @return array<int, int>
     */
    public function favoriteServiceIds(): array
    {
        return $this->favorites()
            ->where('favoritable_type', Service::class)
            ->pluck('favoritable_id')
            ->all();
    }

    public function balance()
    {
        return $this->hasOne(VendorBalance::class);
    }

    public function walletTransactions()
    {
        return $this->hasMany(VendorWalletTransaction::class);
    }

    public function withdrawalRequests()
    {
        return $this->hasMany(WithdrawalRequest::class);
    }
}
