<?php

namespace App\Notifications;

use App\Models\WithdrawalRequest;

/** Retour au vendeur sur sa demande de retrait. */
class WithdrawalProcessedNotification extends BaseSiteNotification
{
    public function __construct(
        private WithdrawalRequest $request,
        private string $outcome // approved, rejected, paid
    ) {}

    protected function payload(): array
    {
        $amount = number_format($this->request->amount, 0, ',', ' ');

        [$title, $message, $icon] = match ($this->outcome) {
            'approved' => ['Retrait approuvé', "Votre retrait de {$amount} FCFA a été approuvé et sera versé sous peu.", 'success'],
            'paid' => ['Retrait versé', "Votre retrait de {$amount} FCFA a été versé sur le {$this->request->phone_number}.", 'success'],
            default => ['Retrait refusé', "Votre demande de retrait de {$amount} FCFA a été refusée." . ($this->request->admin_notes ? " Motif : {$this->request->admin_notes}" : ''), 'cancel'],
        };

        return [
            'type' => 'withdrawal.' . $this->outcome,
            'title' => $title,
            'message' => $message,
            'url' => route('backoffice.supplier.wallet.index'),
            'withdrawal_id' => $this->request->id,
            'icon' => $icon,
        ];
    }
}
