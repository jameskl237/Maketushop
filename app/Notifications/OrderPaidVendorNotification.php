<?php

namespace App\Notifications;

use App\Models\Order;

/** Le vendeur est prévenu qu'une de ses commandes vient d'être payée. */
class OrderPaidVendorNotification extends BaseSiteNotification
{
    public function __construct(
        private Order $order,
        private int $vendorAmount
    ) {}

    protected function payload(): array
    {
        return [
            'type' => 'order.paid',
            'title' => 'Nouvelle commande payée',
            'message' => sprintf(
                'La commande %s a été réglée. Votre part : %s FCFA, versée après confirmation de réception par le client.',
                $this->order->order_number,
                number_format($this->vendorAmount, 0, ',', ' ')
            ),
            'url' => route('backoffice.supplier.orders.show', $this->order->id),
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'icon' => 'payment',
        ];
    }
}
