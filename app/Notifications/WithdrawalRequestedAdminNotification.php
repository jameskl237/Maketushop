<?php

namespace App\Notifications;

use App\Models\WithdrawalRequest;

/** Une demande de retrait vendeur attend l'arbitrage de l'admin. */
class WithdrawalRequestedAdminNotification extends BaseSiteNotification
{
    public function __construct(private WithdrawalRequest $request) {}

    protected function payload(): array
    {
        return [
            'type' => 'withdrawal.requested',
            'title' => 'Demande de retrait',
            'message' => sprintf(
                '%s demande un retrait de %s FCFA vers le %s (%s).',
                $this->request->user?->name ?? 'Un vendeur',
                number_format($this->request->amount, 0, ',', ' '),
                $this->request->phone_number,
                $this->request->operator
            ),
            'url' => route('backoffice.admin.withdrawals.index'),
            'withdrawal_id' => $this->request->id,
            'icon' => 'wallet',
        ];
    }
}
