<?php

namespace App\Notifications;

use App\Models\Order;

/** 72 h sans livraison : on propose au client d'annuler et d'être remboursé. */
class CancellationOfferedNotification extends BaseSiteNotification
{
    public function __construct(private Order $order) {}

    protected function payload(): array
    {
        return [
            'type' => 'order.cancellation_offered',
            'title' => 'Commande toujours pas livrée',
            'message' => sprintf(
                'La commande %s n\'est pas confirmée comme livrée après %d h. Vous pouvez l\'annuler et demander un remboursement, ou patienter encore.',
                $this->order->order_number,
                Order::CANCELLATION_OFFER_HOURS
            ),
            'url' => route('orders.track', $this->order->id),
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'icon' => 'warning',
        ];
    }
}
