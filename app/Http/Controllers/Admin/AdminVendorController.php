<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VendorBalance;
use App\Services\Wallet\VendorWalletService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminVendorController extends Controller
{
    public function __construct(
        private VendorWalletService $walletService
    ) {
        $this->middleware(['auth', 'role:admin,superadmin']);
    }

    public function index()
    {
        $vendors = User::where('role', User::ROLE_SUPPLIER)
            ->withCount('products', 'shops')
            ->with('balance')
            ->latest()
            ->paginate(20);

        return Inertia::render('Backoffice/Admin/Vendors/Index', [
            'vendors' => $vendors,
        ]);
    }

    public function show(User $vendor)
    {
        if ($vendor->role !== User::ROLE_SUPPLIER) {
            abort(404);
        }

        $vendor->load(['shops', 'products', 'balance']);

        return Inertia::render('Backoffice/Admin/Vendors/Show', [
            'vendor' => $vendor,
            'stats' => $this->walletService->getStats($vendor),
            'transactions' => $this->walletService->getTransactions($vendor, 100),
        ]);
    }
}
