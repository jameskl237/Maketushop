<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Services\Wallet\VendorWalletService;
use App\Services\Wallet\WithdrawalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class VendorWalletController extends Controller
{
    public function __construct(
        private VendorWalletService $walletService,
        private WithdrawalService $withdrawalService
    ) {
        $this->middleware(['auth', 'role:supplier']);
    }

    public function index()
    {
        $vendor = Auth::user();

        return Inertia::render('Backoffice/Vendor/Wallet', [
            'stats' => $this->walletService->getStats($vendor),
            'transactions' => $this->walletService->getTransactions($vendor),
            'withdrawals' => $this->withdrawalService->getVendorRequests($vendor),
            'minWithdrawal' => config('payments.min_withdrawal', 500),
        ]);
    }

    public function requestWithdrawal(Request $request)
    {
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:' . config('payments.min_withdrawal', 500)],
            'phone_number' => ['required', 'string', 'max:20'],
            'operator' => ['required', 'string', 'in:ORANGE_MONEY,MTN_MOMO'],
        ]);

        try {
            $withdrawal = $this->withdrawalService->request(
                Auth::user(),
                $validated['amount'],
                $validated['phone_number'],
                $validated['operator']
            );

            return back()->with('success', 'Demande de retrait soumise. En attente de validation.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
