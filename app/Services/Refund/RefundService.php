<?php

namespace App\Services\Refund;

use App\Models\Order;
use App\Models\RefundRequest;
use App\Models\User;
use App\Models\VendorBalance;
use App\Notifications\OrderCancelledNotification;
use App\Notifications\RefundRequestedAdminNotification;
use App\Services\Order\OrderService;
use Illuminate\Support\Facades\DB;

/**
 * Annulation d'une commande payée et suivi du remboursement.
 *
 * Le virement est effectué à la main par l'administration depuis le back-office
 * CinetPay ; ce service ne fait qu'ouvrir, tracer et clôturer la demande.
 */
class RefundService
{
    public const REASON_CLIENT = 'annulée par le client';
    public const REASON_AUTOMATIC = 'délai de livraison dépassé';

    /**
     * Annule la commande, reprend l'escrow du vendeur et ouvre la demande
     * de remboursement destinée à l'admin.
     */
    public function cancelAndRefund(Order $order, string $reason, bool $automatic = false): RefundRequest
    {
        if (!$order->isPaid()) {
            throw new \RuntimeException('Une commande non payée n\'a pas à être remboursée');
        }

        if ($order->isDelivered()) {
            throw new \RuntimeException('Une commande déjà confirmée comme livrée ne peut plus être annulée');
        }

        if ($order->isCancelled()) {
            throw new \RuntimeException('Cette commande est déjà annulée');
        }

        $refund = DB::transaction(function () use ($order, $reason) {
            $order->update([
                'status' => Order::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
                'is_delivered' => false,
            ]);

            $this->reverseVendorEscrow($order);

            return RefundRequest::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'amount' => (int) round((float) $order->total_price),
                'currency' => 'XOF',
                'status' => RefundRequest::STATUS_PENDING,
                'reason' => $reason,
                'phone_number' => $order->phone_number,
                'transaction_id' => $order->payment?->transaction_id,
            ]);
        });

        $order->user?->notify(new OrderCancelledNotification($order->fresh(), $reason, $automatic));
        $this->notifyAdmins($refund->load(['order', 'user']));

        return $refund;
    }

    public function markAsRefunded(RefundRequest $refund, User $admin, ?string $notes = null): void
    {
        if (!$refund->isPending()) {
            throw new \RuntimeException('Cette demande a déjà été traitée');
        }

        $refund->update([
            'status' => RefundRequest::STATUS_REFUNDED,
            'processed_by' => $admin->id,
            'admin_notes' => $notes,
            'refunded_at' => now(),
        ]);
    }

    public function reject(RefundRequest $refund, User $admin, ?string $notes = null): void
    {
        if (!$refund->isPending()) {
            throw new \RuntimeException('Cette demande a déjà été traitée');
        }

        $refund->update([
            'status' => RefundRequest::STATUS_REJECTED,
            'processed_by' => $admin->id,
            'admin_notes' => $notes,
            'rejected_at' => now(),
        ]);
    }

    public function pending()
    {
        return RefundRequest::pending()
            ->with(['order', 'user'])
            ->latest()
            ->paginate(20);
    }

    /**
     * L'argent n'a jamais été acquis au vendeur : on le retire de son escrow.
     * Le calcul reprend exactement la répartition faite à l'encaissement.
     */
    private function reverseVendorEscrow(Order $order): void
    {
        foreach (app(OrderService::class)->vendorSharesFor($order) as $vendorId => $share) {
            VendorBalance::initForUser($vendorId)->reversePending(
                $share['vendor_amount'],
                "Annulation - Commande #{$order->order_number}",
                $order
            );
        }
    }

    private function notifyAdmins(RefundRequest $refund): void
    {
        User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPERADMIN])
            ->get()
            ->each
            ->notify(new RefundRequestedAdminNotification($refund));
    }
}
