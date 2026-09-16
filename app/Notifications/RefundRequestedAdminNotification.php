<?php

namespace App\Notifications;

use App\Models\RefundRequest;

/** L'admin doit effectuer le virement de remboursement à la main. */
class RefundRequestedAdminNotification extends BaseSiteNotification
{
    public function __construct(private RefundRequest $refund) {}

    protected function payload(): array
    {
        return [
            'type' => 'refund.requested',
            'title' => 'Remboursement à traiter',
            'message' => sprintf(
                'Commande %s annulée : %s FCFA à rembourser à %s.',
                $this->refund->order?->order_number ?? '—',
                number_format($this->refund->amount, 0, ',', ' '),
                $this->refund->user?->name ?? 'client'
            ),
            'url' => route('backoffice.admin.refunds.index'),
            'refund_id' => $this->refund->id,
            'icon' => 'refund',
        ];
    }
}
