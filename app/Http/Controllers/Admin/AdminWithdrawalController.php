<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalRequest;
use App\Services\Wallet\WithdrawalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AdminWithdrawalController extends Controller
{
    public function __construct(
        private WithdrawalService $withdrawalService
    ) {
        $this->middleware(['auth', 'role:admin,superadmin']);
    }

    public function index()
    {
        return Inertia::render('Backoffice/Admin/Withdrawals/Index', [
            'pendingWithdrawals' => WithdrawalRequest::pending()
                ->with('user')
                ->latest()
                ->paginate(20),
            'allWithdrawals' => WithdrawalRequest::with('user')
                ->latest()
                ->paginate(20),
        ]);
    }

    public function approve(WithdrawalRequest $request)
    {
        try {
            $this->withdrawalService->approve($request, Auth::user());
            return back()->with('success', 'Demande de retrait approuvée.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, WithdrawalRequest $withdrawalRequest)
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->withdrawalService->reject($withdrawalRequest, Auth::user(), $validated['reason'] ?? null);
            return back()->with('success', 'Demande de retrait rejetée.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function markPaid(WithdrawalRequest $request)
    {
        try {
            $this->withdrawalService->markAsPaid($request, Auth::user());
            return back()->with('success', 'Retrait marqué comme payé.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
