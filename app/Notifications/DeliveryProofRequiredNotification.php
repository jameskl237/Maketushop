<?php

namespace App\Notifications;

use App\Models\Order;

/** Rappel au vendeur : sans photo de livraison, le client ne peut pas confirmer. */
class DeliveryProofRequiredNotification extends BaseSiteNotification
{
    public function __construct(private Order $order) {}

    protected function payload(): array
    {
        return [
            'type' => 'order.proof_required',
            'title' => 'Preuve de livraison attendue',
            'message' => sprintf(
                'La commande %s attend votre photo de livraison. Sans elle, le client ne peut pas confirmer la réception et vous ne serez pas payé.',
                $this->order->order_number
            ),
            'url' => route('backoffice.supplier.orders.show', $this->order->id),
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'icon' => 'warning',
        ];
    }
}
