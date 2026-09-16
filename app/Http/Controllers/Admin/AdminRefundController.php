<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RefundRequest;
use App\Services\Refund\RefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Suivi des remboursements. Le virement est effectué hors application, depuis
 * le back-office CinetPay ; l'admin vient ensuite clôturer la demande ici.
 */
class AdminRefundController extends Controller
{
    public function __construct(
        private RefundService $refunds
    ) {
    }

    public function index(Request $request)
    {
        $status = $request->query('status', RefundRequest::STATUS_PENDING);

        $requests = RefundRequest::query()
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->with(['order', 'user'])
            ->latest()
            ->paginate(20)
            ->through(fn (RefundRequest $r) => [
                'id' => $r->id,
                'amount' => $r->amount,
                'currency' => $r->currency,
                'status' => $r->status,
                'reason' => $r->reason,
                'phone_number' => $r->phone_number,
                'transaction_id' => $r->transaction_id,
                'admin_notes' => $r->admin_notes,
                'created_at' => $r->created_at,
                'refunded_at' => $r->refunded_at,
                'rejected_at' => $r->rejected_at,
                'order_number' => $r->order?->order_number,
                'order_id' => $r->order_id,
                'customer' => $r->user?->name,
                'customer_email' => $r->user?->email,
            ]);

        return Inertia::render('Backoffice/Admin/Refunds/Index', [
            'requests' => $requests,
            'currentStatus' => $status,
            'statuses' => [
                'pending' => 'À rembourser',
                'refunded' => 'Remboursées',
                'rejected' => 'Refusées',
                'all' => 'Toutes',
            ],
            'pendingTotal' => RefundRequest::pending()->sum('amount'),
        ]);
    }

    public function markRefunded(Request $request, RefundRequest $refundRequest)
    {
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->refunds->markAsRefunded($refundRequest, Auth::user(), $validated['admin_notes'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Remboursement enregistré.');
    }

    public function reject(Request $request, RefundRequest $refundRequest)
    {
        $validated = $request->validate([
            'admin_notes' => ['required', 'string', 'max:255'],
        ], [
            'admin_notes.required' => 'Indiquez le motif du refus.',
        ]);

        try {
            $this->refunds->reject($refundRequest, Auth::user(), $validated['admin_notes']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Demande de remboursement refusée.');
    }
}
