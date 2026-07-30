<?php

namespace App\Services\Wallet;

use App\Models\User;
use App\Models\VendorBalance;
use App\Models\VendorWalletTransaction;
use Illuminate\Support\Collection;

class VendorWalletService
{
    public function getBalance(User $vendor): VendorBalance
    {
        return VendorBalance::initForUser($vendor->id);
    }

    public function getTransactions(User $vendor, int $limit = 50): Collection
    {
        return VendorWalletTransaction::where('user_id', $vendor->id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function canWithdraw(User $vendor, int $amount): array
    {
        $balance = $this->getBalance($vendor);

        if ($amount <= 0) {
            return ['can' => false, 'reason' => 'Le montant doit être supérieur à 0'];
        }

        if ($amount > $balance->available_balance) {
            return ['can' => false, 'reason' => 'Solde disponible insuffisant'];
        }

        $minWithdrawal = (int) config('payments.min_withdrawal', 500);
        if ($amount < $minWithdrawal) {
            return ['can' => false, 'reason' => "Montant minimum de retrait : {$minWithdrawal} FCFA"];
        }

        return ['can' => true, 'reason' => null];
    }

    public function getStats(User $vendor): array
    {
        $balance = $this->getBalance($vendor);

        return [
            'available_balance' => $balance->available_balance,
            'pending_balance' => $balance->pending_balance,
            'total_earned' => $balance->total_earned,
            'total_withdrawn' => $balance->total_withdrawn,
        ];
    }
}
