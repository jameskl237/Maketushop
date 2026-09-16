<?php

namespace App\Notifications;

use App\Models\Order;

/** Le client a confirmé la réception : l'escrow du vendeur est libéré. */
class OrderConfirmedVendorNotification extends BaseSiteNotification
{
    public function __construct(
        private Order $order,
        private int $releasedAmount
    ) {}

    protected function payload(): array
    {
        return [
            'type' => 'order.confirmed',
            'title' => 'Livraison confirmée par le client',
            'message' => sprintf(
                'Le client a confirmé la réception de la commande %s. %s FCFA ont été versés sur votre solde disponible.',
                $this->order->order_number,
                number_format($this->releasedAmount, 0, ',', ' ')
            ),
            'url' => route('backoffice.supplier.wallet.index'),
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'icon' => 'success',
        ];
    }
}
