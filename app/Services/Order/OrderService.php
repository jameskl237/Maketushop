<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\VendorBalance;
use App\Models\VendorWalletTransaction;
use App\Notifications\CancellationOfferedNotification;
use App\Notifications\OrderConfirmedVendorNotification;
use App\Notifications\OrderPaidClientNotification;
use App\Notifications\OrderPaidVendorNotification;
use App\Services\Receipt\ReceiptService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OrderService
{
    public const VENDOR_STATUS_PENDING = 'pending';
    public const VENDOR_STATUS_ACCEPTED = 'accepted';
    public const VENDOR_STATUS_PREPARING = 'preparing';
    public const VENDOR_STATUS_SHIPPED = 'shipped';
    public const VENDOR_STATUS_DELIVERED = 'delivered';

    public const PLATFORM_FEE_PERCENT = 10;

    public function createOrder(int $userId, array $delivery, array $items, string $paymentMethod = 'online'): Order
    {
        return DB::transaction(function () use ($userId, $delivery, $items, $paymentMethod) {
            $reference = $this->generateOrderNumber();

            $totalPrice = collect($items)->sum(fn ($item) => (float) $item['price'] * (int) $item['quantity']);
            $totalQty = collect($items)->sum('quantity');

            $order = Order::create([
                'order_number' => $reference,
                'user_id' => $userId,
                'customer_first_name' => $delivery['first_name'],
                'customer_last_name' => $delivery['last_name'],
                'delivery_address' => $delivery['delivery_address'],
                'phone_number' => $delivery['phone_number'],
                'total_products' => $totalQty,
                'total_price' => $totalPrice,
                'status' => Order::STATUS_PENDING,
                'is_paid' => false,
                'is_delivered' => false,
                'payment_method' => $paymentMethod === 'cod' ? 'cod' : 'cinetpay',
                'vendor_status' => self::VENDOR_STATUS_PENDING,
                'platform_fee' => 0,
                'vendor_amount' => 0,
            ]);

            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $order->products()->attach($product->id, [
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }

            return $order->fresh();
        });
    }

    /**
     * Paiement validé : la commande passe « en cours », l'argent du vendeur part
     * en escrow, le reçu est généré et les deux parties sont notifiées.
     *
     * Idempotent : un second webhook CinetPay ne recrédite pas l'escrow.
     */
    public function markAsPaid(Order $order): void
    {
        if ($order->isPaid()) {
            return;
        }

        $order->update([
            'is_paid' => true,
            'paid_at' => now(),
            'status' => Order::STATUS_IN_PROGRESS,
        ]);

        $credited = $this->creditVendorPendingBalance($order);

        $this->generateReceipt($order);
        $this->notifyPayment($order->fresh(), $credited);
    }

    /**
     * Le vendeur atteste la livraison en déposant une photo. Cela ne libère pas
     * l'escrow : seule la confirmation du client le fait.
     */
    public function attachDeliveryProof(Order $order, int $vendorId, UploadedFile $photo): void
    {
        if (!$order->isPaid()) {
            throw new \RuntimeException('La commande n\'est pas encore payée');
        }

        if ($order->isCancelled()) {
            throw new \RuntimeException('Cette commande a été annulée');
        }

        if ($order->isDelivered()) {
            throw new \RuntimeException('La livraison est déjà confirmée par le client');
        }

        $path = $photo->store('delivery-proofs', 'public');

        // Une nouvelle preuve remplace la précédente : on ne garde pas d'orphelin.
        $previous = $order->delivery_proof_path;

        $order->update([
            'delivery_proof_path' => $path,
            'delivery_proof_at' => now(),
            'vendor_status' => self::VENDOR_STATUS_DELIVERED,
            'vendor_delivered_at' => now(),
        ]);

        if ($previous && $previous !== $path) {
            Storage::disk('public')->delete($previous);
        }
    }

    /**
     * Le client confirme avoir reçu sa commande : statut « livrée » et
     * libération de l'escrow vers le solde disponible des vendeurs.
     */
    public function confirmReceptionByClient(Order $order): void
    {
        if ($order->isDelivered()) {
            return;
        }

        if (!$order->canBeConfirmedByClient()) {
            throw new \RuntimeException(
                $order->hasDeliveryProof()
                    ? 'Cette commande ne peut pas être confirmée'
                    : 'Le vendeur n\'a pas encore déposé sa preuve de livraison'
            );
        }

        $released = DB::transaction(function () use ($order) {
            $order->update([
                'status' => Order::STATUS_DELIVERED,
                'is_delivered' => true,
                'client_confirmed_at' => now(),
                'escrow_released_at' => now(),
            ]);

            return $this->releaseEscrowToVendor($order);
        });

        foreach ($released as $vendorId => $amount) {
            $this->notifyVendor($vendorId, new OrderConfirmedVendorNotification($order->fresh(), $amount));
        }
    }

    /** Propose au client d'annuler après le délai sans livraison confirmée. */
    public function offerCancellation(Order $order): void
    {
        if ($order->cancellation_offered_at || !$order->isInProgress()) {
            return;
        }

        $order->update(['cancellation_offered_at' => now()]);
        $order->user?->notify(new CancellationOfferedNotification($order->fresh()));
    }

    /** Le client refuse d'annuler : on repart pour le délai d'attente final. */
    public function declineCancellation(Order $order): void
    {
        if (!$order->cancellation_offered_at) {
            throw new \RuntimeException('Aucune annulation n\'a été proposée pour cette commande');
        }

        if (!$order->isInProgress()) {
            throw new \RuntimeException('Cette commande n\'est plus en cours');
        }

        $order->update(['cancellation_declined_at' => now()]);
    }

    private function generateReceipt(Order $order): void
    {
        try {
            app(ReceiptService::class)->refresh($order);
        } catch (\Throwable $e) {
            // Un reçu manquant ne doit jamais bloquer l'encaissement : il sera
            // régénéré à la demande au premier téléchargement.
            Log::warning('Order.receipt_generation_failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** @param array<int,int> $credited  montant en escrow par vendeur */
    private function notifyPayment(Order $order, array $credited): void
    {
        $order->user?->notify(new OrderPaidClientNotification($order));

        foreach ($credited as $vendorId => $amount) {
            $this->notifyVendor($vendorId, new OrderPaidVendorNotification($order, $amount));
        }
    }

    private function notifyVendor(int $vendorId, $notification): void
    {
        User::find($vendorId)?->notify($notification);
    }

    public function acceptByVendor(Order $order, int $vendorId): void
    {
        if ($order->vendor_status !== self::VENDOR_STATUS_PENDING) {
            throw new \RuntimeException('La commande ne peut plus être acceptée');
        }

        $order->update([
            'vendor_status' => self::VENDOR_STATUS_ACCEPTED,
            'vendor_accepted_at' => now(),
        ]);
    }

    public function markAsPreparing(Order $order, int $vendorId): void
    {
        if ($order->vendor_status !== self::VENDOR_STATUS_ACCEPTED) {
            throw new \RuntimeException('La commande doit d\'abord être acceptée');
        }

        $order->update([
            'vendor_status' => self::VENDOR_STATUS_PREPARING,
            'vendor_preparing_at' => now(),
        ]);
    }

    public function markAsShipped(Order $order, int $vendorId): void
    {
        if ($order->vendor_status !== self::VENDOR_STATUS_PREPARING) {
            throw new \RuntimeException('La commande doit d\'abord être en préparation');
        }

        $order->update([
            'vendor_status' => self::VENDOR_STATUS_SHIPPED,
            'vendor_shipped_at' => now(),
        ]);
    }

    /**
     * @deprecated Le vendeur atteste désormais la livraison via attachDeliveryProof(),
     *             et c'est la confirmation du client qui clôture la commande.
     */
    public function markAsDelivered(Order $order, int $vendorId): void
    {
        throw new \RuntimeException(
            'Déposez une photo de livraison : la commande sera clôturée par la confirmation du client.'
        );
    }

    public function getVendorOrders(int $vendorId, ?string $vendorStatus = null)
    {
        $query = Order::whereHas('products', function ($q) use ($vendorId) {
            $q->where('user_id', $vendorId);
        })->with(['products' => function ($q) use ($vendorId) {
            $q->where('user_id', $vendorId);
        }, 'user']);

        if ($vendorStatus) {
            $query->where('vendor_status', $vendorStatus);
        }

        return $query->latest()->paginate(20);
    }

    /**
     * Répartition du montant d'une commande entre ses vendeurs, après commission.
     *
     * Les quantités et prix se lisent sur $product->pivot : Laravel réserve le
     * préfixe « pivot_ » et vide les colonnes ainsi aliasées dans un select.
     *
     * @return array<int,array{subtotal:float,fee:int,vendor_amount:int}>
     */
    private function vendorShares(Order $order): array
    {
        $shares = [];

        foreach ($order->products()->get()->groupBy('user_id') as $vendorId => $products) {
            $subtotal = $products->sum(
                fn ($p) => (float) $p->pivot->price * (int) $p->pivot->quantity
            );
            $fee = (int) round($subtotal * self::PLATFORM_FEE_PERCENT / 100);

            $shares[(int) $vendorId] = [
                'subtotal' => $subtotal,
                'fee' => $fee,
                'vendor_amount' => (int) round($subtotal - $fee),
            ];
        }

        return $shares;
    }

    /** @return array<int,int> montant mis en escrow, par identifiant de vendeur */
    private function creditVendorPendingBalance(Order $order): array
    {
        $credited = [];

        foreach ($this->vendorShares($order) as $vendorId => $share) {
            $order->update([
                'platform_fee' => $order->platform_fee + $share['fee'],
                'vendor_amount' => $order->vendor_amount + $share['vendor_amount'],
            ]);

            VendorBalance::initForUser($vendorId)->addPending(
                $share['vendor_amount'],
                "Commande #{$order->order_number} (en attente de livraison)",
                $order
            );

            $credited[$vendorId] = $share['vendor_amount'];
        }

        return $credited;
    }

    /** @return array<int,int> montant libéré, par identifiant de vendeur */
    private function releaseEscrowToVendor(Order $order): array
    {
        $released = [];

        foreach ($this->vendorShares($order) as $vendorId => $share) {
            VendorBalance::initForUser($vendorId)->releaseToAvailable(
                $share['vendor_amount'],
                "Libération du paiement - Commande #{$order->order_number}",
                $order
            );

            $released[$vendorId] = $share['vendor_amount'];
        }

        return $released;
    }

    /** Exposé pour les annulations : même répartition, en sens inverse. */
    public function vendorSharesFor(Order $order): array
    {
        return $this->vendorShares($order);
    }

    private function generateOrderNumber(): string
    {
        do {
            $reference = 'CMD-' . strtoupper(Str::random(10));
        } while (Order::where('order_number', $reference)->exists());

        return $reference;
    }
}
