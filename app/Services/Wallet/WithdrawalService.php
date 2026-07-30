<?php

namespace App\Services\Wallet;

use App\Models\User;
use App\Models\VendorBalance;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\DB;

class WithdrawalService
{
    public function __construct(
        private VendorWalletService $walletService
    ) {}

    public function request(User $vendor, int $amount, string $phoneNumber, string $operator): WithdrawalRequest
    {
        $check = $this->walletService->canWithdraw($vendor, $amount);

        if (!$check['can']) {
            throw new \RuntimeException($check['reason']);
        }

        return DB::transaction(function () use ($vendor, $amount, $phoneNumber, $operator) {
            $balance = VendorBalance::initForUser($vendor->id);
            $balance->holdForWithdrawal($amount);

            $request = WithdrawalRequest::create([
                'user_id' => $vendor->id,
                'amount' => $amount,
                'phone_number' => $phoneNumber,
                'operator' => $operator,
                'status' => WithdrawalRequest::STATUS_PENDING,
            ]);

            return $request;
        });
    }

    public function approve(WithdrawalRequest $request, User $admin): void
    {
        if (!$request->isPending()) {
            throw new \RuntimeException('Cette demande a déjà été traitée');
        }

        $request->update([
            'status' => WithdrawalRequest::STATUS_APPROVED,
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);
    }

    public function reject(WithdrawalRequest $request, User $admin, ?string $reason = null): void
    {
        if (!$request->isPending()) {
            throw new \RuntimeException('Cette demande a déjà été traitée');
        }

        DB::transaction(function () use ($request, $admin, $reason) {
            $balance = VendorBalance::initForUser($request->user_id);
            $balance->releaseHeld($request->amount);

            $request->update([
                'status' => WithdrawalRequest::STATUS_REJECTED,
                'approved_by' => $admin->id,
                'admin_notes' => $reason,
                'rejected_at' => now(),
            ]);
        });
    }

    public function markAsPaid(WithdrawalRequest $request, User $admin): void
    {
        if (!$request->isApproved()) {
            throw new \RuntimeException('La demande doit d\'abord être approuvée');
        }

        DB::transaction(function () use ($request, $admin) {
            $balance = VendorBalance::initForUser($request->user_id);
            $balance->withdraw($request->amount, "Retrait #{$request->id}", $request);

            $request->update([
                'status' => WithdrawalRequest::STATUS_PAID,
                'paid_at' => now(),
            ]);
        });
    }

    public function getPendingRequests()
    {
        return WithdrawalRequest::pending()
            ->with('user')
            ->latest()
            ->paginate(20);
    }

    public function getVendorRequests(User $vendor)
    {
        return WithdrawalRequest::where('user_id', $vendor->id)
            ->latest()
            ->paginate(20);
    }
}
