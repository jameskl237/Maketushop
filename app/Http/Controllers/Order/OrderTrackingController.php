<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Order\OrderService;
use App\Services\Receipt\ReceiptService;
use App\Services\Refund\RefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/**
 * Suivi de commande côté acheteur : état, reçu PDF, partage WhatsApp,
 * confirmation de réception et arbitrage de l'annulation.
 */
class OrderTrackingController extends Controller
{
    public function __construct(
        private OrderService $orders,
        private ReceiptService $receipts,
        private RefundService $refunds
    ) {
    }

    public function index()
    {
        $orders = Order::where('user_id', Auth::id())
            ->with(['products.shop'])
            ->latest()
            ->paginate(15)
            ->through(fn (Order $order) => $this->summary($order));

        return Inertia::render('Orders/Index', ['orders' => $orders]);
    }

    public function show(Order $order)
    {
        $this->authorizeOwner($order);

        $order->load(['products.shop', 'products.supplier', 'payment', 'refundRequest']);

        return Inertia::render('Orders/Track', [
            'order' => array_merge($this->summary($order), [
                'delivery_address' => $order->delivery_address,
                'phone_number' => $order->phone_number,
                'customer_name' => trim($order->customer_first_name . ' ' . $order->customer_last_name),
                'items' => $order->products->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'vendor' => $p->shop?->name ?? $p->supplier?->name,
                    'quantity' => (int) $p->pivot->quantity,
                    'price' => (float) $p->pivot->price,
                ]),
                'delivery_proof_url' => $order->delivery_proof_path
                    ? Storage::disk('public')->url($order->delivery_proof_path)
                    : null,
                'refund' => $order->refundRequest ? [
                    'status' => $order->refundRequest->status,
                    'amount' => $order->refundRequest->amount,
                    'requested_at' => $order->refundRequest->created_at,
                ] : null,
            ]),
            'receipt' => $order->isPaid() ? [
                'download_url' => route('orders.receipt', $order->id),
                'whatsapp_url' => $this->receipts->whatsappShareUrl($order),
                'whatsapp_vendor_url' => $this->vendorWhatsappUrl($order),
            ] : null,
        ]);
    }

    /** Téléchargement du reçu : propriétaire connecté, ou lien signé partagé. */
    public function receipt(Request $request, Order $order)
    {
        if (!$request->hasValidSignature() && Auth::id() !== $order->user_id) {
            abort(403, 'Ce reçu ne vous appartient pas.');
        }

        if (!$order->isPaid()) {
            abort(404, 'Aucun reçu : cette commande n\'est pas payée.');
        }

        return response($this->receipts->content($order), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $this->receipts->filename($order) . '"',
        ]);
    }

    public function confirmReception(Order $order)
    {
        $this->authorizeOwner($order);

        try {
            $this->orders->confirmReceptionByClient($order);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Réception confirmée. Le vendeur a été payé, merci !');
    }

    /** Le client accepte l'annulation proposée après le délai. */
    public function cancel(Order $order)
    {
        $this->authorizeOwner($order);

        if (!$order->cancellation_offered_at) {
            return back()->with('error', 'L\'annulation n\'est pas encore proposée pour cette commande.');
        }

        try {
            $this->refunds->cancelAndRefund($order, RefundService::REASON_CLIENT);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Commande annulée. Votre remboursement a été transmis à l\'administration.');
    }

    /** Le client refuse d'annuler et accorde un délai supplémentaire au vendeur. */
    public function declineCancellation(Order $order)
    {
        $this->authorizeOwner($order);

        try {
            $this->orders->declineCancellation($order);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', sprintf(
            'Vous patientez encore. Sans livraison confirmée d\'ici %d jours, la commande sera annulée et remboursée.',
            Order::AUTO_CANCEL_DAYS
        ));
    }

    private function summary(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'status_label' => $this->statusLabel($order),
            'total_price' => (float) $order->total_price,
            'total_products' => $order->total_products,
            'is_paid' => $order->isPaid(),
            'paid_at' => $order->paid_at,
            'created_at' => $order->created_at,
            'has_delivery_proof' => $order->hasDeliveryProof(),
            'delivery_proof_at' => $order->delivery_proof_at,
            'confirmed_at' => $order->client_confirmed_at,
            'can_confirm' => $order->canBeConfirmedByClient(),
            'cancellation_offered' => (bool) $order->cancellation_offered_at,
            'cancellation_declined' => (bool) $order->cancellation_declined_at,
            'auto_cancel_at' => $order->autoCancelAt(),
            'cancelled_at' => $order->cancelled_at,
            'cancellation_reason' => $order->cancellation_reason,
        ];
    }

    private function statusLabel(Order $order): string
    {
        return match ($order->status) {
            Order::STATUS_PENDING => 'En attente de paiement',
            Order::STATUS_IN_PROGRESS => 'En cours',
            Order::STATUS_DELIVERED => 'Livrée',
            Order::STATUS_CANCELLED => 'Annulée',
            default => ucfirst((string) $order->status),
        };
    }

    /** Lien WhatsApp prérempli vers le premier vendeur joignable de la commande. */
    private function vendorWhatsappUrl(Order $order): ?string
    {
        $phone = $order->products
            ->map(fn ($p) => $p->shop?->phone ?? $p->supplier?->phone)
            ->filter()
            ->first();

        return $phone ? $this->receipts->whatsappShareUrl($order, $phone) : null;
    }

    private function authorizeOwner(Order $order): void
    {
        abort_unless($order->user_id === Auth::id(), 403, 'Cette commande ne vous appartient pas.');
    }
}
