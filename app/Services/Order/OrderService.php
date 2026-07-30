<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\Product;
use App\Models\VendorBalance;
use App\Models\VendorWalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public const VENDOR_STATUS_PENDING = 'pending';
    public const VENDOR_STATUS_ACCEPTED = 'accepted';
    public const VENDOR_STATUS_PREPARING = 'preparing';
    public const VENDOR_STATUS_SHIPPED = 'shipped';
    public const VENDOR_STATUS_DELIVERED = 'delivered';

    public const PLATFORM_FEE_PERCENT = 10;

    public function createOrder(int $userId, array $delivery, array $items): Order
    {
        return DB::transaction(function () use ($userId, $delivery, $items) {
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
                'payment_method' => 'cinetpay',
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

    public function markAsPaid(Order $order): void
    {
        $order->update([
            'is_paid' => true,
            'status' => Order::STATUS_PENDING,
        ]);

        $this->creditVendorPendingBalance($order);
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

    public function markAsDelivered(Order $order, int $vendorId): void
    {
        if ($order->vendor_status !== self::VENDOR_STATUS_SHIPPED) {
            throw new \RuntimeException('La commande doit d\'abord être expédiée');
        }

        DB::transaction(function () use ($order) {
            $order->update([
                'vendor_status' => self::VENDOR_STATUS_DELIVERED,
                'vendor_delivered_at' => now(),
                'status' => Order::STATUS_DELIVERED,
                'is_delivered' => true,
                'escrow_released_at' => now(),
            ]);

            $this->releaseEscrowToVendor($order);
        });
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

    private function creditVendorPendingBalance(Order $order): void
    {
        $vendorProducts = $order->products()
            ->select('products.*', 'order_product.quantity as pivot_quantity', 'order_product.price as pivot_price')
            ->get()
            ->groupBy('user_id');

        foreach ($vendorProducts as $vendorId => $products) {
            $subtotal = $products->sum(fn ($p) => (float) $p->pivot_price * (int) $p->pivot_quantity);
            $fee = (int) round($subtotal * self::PLATFORM_FEE_PERCENT / 100);
            $vendorAmount = (int) round($subtotal - $fee);

            $order->update([
                'platform_fee' => $order->platform_fee + $fee,
                'vendor_amount' => $order->vendor_amount + $vendorAmount,
            ]);

            $balance = VendorBalance::initForUser($vendorId);
            $balance->addPending($vendorAmount, "Commande #{$order->order_number} (en attente de livraison)", $order);
        }
    }

    private function releaseEscrowToVendor(Order $order): void
    {
        $vendorProducts = $order->products()
            ->select('products.*', 'order_product.quantity as pivot_quantity', 'order_product.price as pivot_price')
            ->get()
            ->groupBy('user_id');

        foreach ($vendorProducts as $vendorId => $products) {
            $subtotal = $products->sum(fn ($p) => (float) $p->pivot_price * (int) $p->pivot_quantity);
            $fee = (int) round($subtotal * self::PLATFORM_FEE_PERCENT / 100);
            $vendorAmount = (int) round($subtotal - $fee);

            $balance = VendorBalance::initForUser($vendorId);
            $balance->releaseToAvailable($vendorAmount, "Libération du paiement - Commande #{$order->order_number}", $order);
        }
    }

    private function generateOrderNumber(): string
    {
        do {
            $reference = 'CMD-' . strtoupper(Str::random(10));
        } while (Order::where('order_number', $reference)->exists());

        return $reference;
    }
}
