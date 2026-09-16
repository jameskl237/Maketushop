<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Order extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';          // créée, pas encore payée
    const STATUS_IN_PROGRESS = 'in_progress';  // payée, en cours de livraison
    const STATUS_DELIVERED = 'delivered';      // réception confirmée par le client
    const STATUS_CANCELLED = 'cancelled';

    /** Délai après paiement au bout duquel l'annulation est proposée au client. */
    const CANCELLATION_OFFER_HOURS = 72;

    /** Délai supplémentaire après un refus d'annulation avant annulation d'office. */
    const AUTO_CANCEL_DAYS = 7;

    const VENDOR_STATUS_PENDING = 'pending';
    const VENDOR_STATUS_ACCEPTED = 'accepted';
    const VENDOR_STATUS_PREPARING = 'preparing';
    const VENDOR_STATUS_SHIPPED = 'shipped';
    const VENDOR_STATUS_DELIVERED = 'delivered';

    protected $fillable = [
        'order_number',
        'user_id',
        'customer_first_name',
        'customer_last_name',
        'delivery_address',
        'phone_number',
        'total_products',
        'total_price',
        'status',
        'is_delivered',
        'is_paid',
        'payment_method',
        'vendor_status',
        'platform_fee',
        'vendor_amount',
        'vendor_accepted_at',
        'vendor_preparing_at',
        'vendor_shipped_at',
        'vendor_delivered_at',
        'escrow_released_at',
        'paid_at',
        'delivery_proof_path',
        'delivery_proof_at',
        'client_confirmed_at',
        'cancellation_offered_at',
        'cancellation_declined_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'is_delivered' => 'boolean',
        'is_paid' => 'boolean',
        'total_price' => 'decimal:2',
        'platform_fee' => 'integer',
        'vendor_amount' => 'integer',
        'vendor_accepted_at' => 'datetime',
        'vendor_preparing_at' => 'datetime',
        'vendor_shipped_at' => 'datetime',
        'vendor_delivered_at' => 'datetime',
        'escrow_released_at' => 'datetime',
        'paid_at' => 'datetime',
        'delivery_proof_at' => 'datetime',
        'client_confirmed_at' => 'datetime',
        'cancellation_offered_at' => 'datetime',
        'cancellation_declined_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::saving(function ($order) {
            if ($order->isDirty('status')) {
                if ($order->status === self::STATUS_DELIVERED) {
                    $order->is_delivered = true;
                } else {
                    $order->is_delivered = false;
                }
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot('quantity', 'price');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)
            ->withPivot('quantity', 'price');
    }

    public function payment(): MorphOne
    {
        return $this->morphOne(Payment::class, 'payable');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function refundRequest()
    {
        return $this->hasOne(RefundRequest::class);
    }

    public function isPaid(): bool
    {
        return (bool) $this->is_paid;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /** Le vendeur a-t-il déposé sa preuve de livraison ? */
    public function hasDeliveryProof(): bool
    {
        return filled($this->delivery_proof_path);
    }

    /**
     * Le client ne peut confirmer la réception que si la commande est payée,
     * en cours, et que le vendeur a déposé sa preuve de livraison.
     */
    public function canBeConfirmedByClient(): bool
    {
        return $this->isPaid() && $this->isInProgress() && $this->hasDeliveryProof();
    }

    /** Date à laquelle l'annulation devient proposable au client. */
    public function cancellationOfferableAt(): ?\Illuminate\Support\Carbon
    {
        return $this->paid_at?->copy()->addHours(self::CANCELLATION_OFFER_HOURS);
    }

    /** Date de l'annulation d'office si le client a refusé d'annuler. */
    public function autoCancelAt(): ?\Illuminate\Support\Carbon
    {
        return $this->cancellation_declined_at?->copy()->addDays(self::AUTO_CANCEL_DAYS);
    }

    public function scopeAwaitingDelivery($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS)->where('is_paid', true);
    }

    public function vendorIds(): array
    {
        return $this->products()
            ->pluck('products.user_id')
            ->unique()
            ->values()
            ->all();
    }
}
