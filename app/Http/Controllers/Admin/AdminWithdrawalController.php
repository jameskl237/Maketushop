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
    }

    public function index(Request $request)
    {
        // Une seule liste paginée : deux paginateurs sur la même page se
        // disputeraient le paramètre « page ».
        $status = $request->query('status', WithdrawalRequest::STATUS_PENDING);

        return Inertia::render('Backoffice/Admin/Withdrawals/Index', [
            'requests' => WithdrawalRequest::query()
                ->when($status !== 'all', fn ($q) => $q->where('status', $status))
                ->with('user:id,name,email')
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'currentStatus' => $status,
            'statuses' => [
                'pending' => 'À traiter',
                'approved' => 'Approuvées',
                'paid' => 'Versées',
                'rejected' => 'Refusées',
                'all' => 'Toutes',
            ],
            'pendingTotal' => (int) WithdrawalRequest::pending()->sum('amount'),
        ]);
    }

    public function approve(WithdrawalRequest $withdrawalRequest)
    {
        try {
            $this->withdrawalService->approve($withdrawalRequest, Auth::user());
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

    public function markPaid(WithdrawalRequest $withdrawalRequest)
    {
        try {
            $this->withdrawalService->markAsPaid($withdrawalRequest, Auth::user());
            return back()->with('success', 'Retrait marqué comme payé.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
