<?php

namespace App\Notifications;

use App\Models\Order;

/** Le client est prévenu que son paiement est validé et son reçu disponible. */
class OrderPaidClientNotification extends BaseSiteNotification
{
    public function __construct(private Order $order) {}

    protected function payload(): array
    {
        return [
            'type' => 'order.paid_client',
            'title' => 'Paiement confirmé',
            'message' => sprintf(
                'Votre paiement pour la commande %s est validé. Votre reçu PDF est disponible.',
                $this->order->order_number
            ),
            'url' => route('orders.track', $this->order->id),
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'icon' => 'receipt',
        ];
    }
}
