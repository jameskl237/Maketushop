<?php

namespace App\Notifications;

use App\Models\Order;

/** Commande annulée, à la demande du client ou automatiquement. */
class OrderCancelledNotification extends BaseSiteNotification
{
    public function __construct(
        private Order $order,
        private string $reason,
        private bool $automatic = false
    ) {}

    protected function payload(): array
    {
        return [
            'type' => 'order.cancelled',
            'title' => $this->automatic ? 'Commande annulée automatiquement' : 'Commande annulée',
            'message' => sprintf(
                'La commande %s a été annulée (%s). Une demande de remboursement de %s FCFA a été transmise à l\'administration.',
                $this->order->order_number,
                $this->reason,
                number_format((int) round((float) $this->order->total_price), 0, ',', ' ')
            ),
            'url' => route('orders.track', $this->order->id),
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'icon' => 'cancel',
        ];
    }
}
