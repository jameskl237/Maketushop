<?php

namespace App\Services\Receipt;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Génère le reçu PDF d'une commande payée.
 *
 * Le fichier est mis en cache sur le disque privé : il est identique tant que
 * la commande ne change pas, et n'a donc pas à être régénéré à chaque téléchargement.
 */
class ReceiptService
{
    private const DISK = 'local';

    public function filename(Order $order): string
    {
        return 'recu-' . $order->order_number . '.pdf';
    }

    public function path(Order $order): string
    {
        return 'receipts/' . $this->filename($order);
    }

    /** Contenu binaire du PDF, généré puis mis en cache au premier appel. */
    public function content(Order $order): string
    {
        $path = $this->path($order);
        $disk = Storage::disk(self::DISK);

        if (!$disk->exists($path)) {
            $disk->put($path, $this->render($order));
        }

        return $disk->get($path);
    }

    /** Force une régénération (montant corrigé, nouvelles infos de livraison...). */
    public function refresh(Order $order): string
    {
        $pdf = $this->render($order);
        Storage::disk(self::DISK)->put($this->path($order), $pdf);

        return $pdf;
    }

    public function render(Order $order): string
    {
        return Pdf::loadView('pdf.receipt', $this->data($order))
            ->setPaper('a4')
            ->output();
    }

    /**
     * Lien de téléchargement signé et temporaire : il peut être transmis
     * à un tiers (WhatsApp) sans exposer le compte du client.
     */
    public function signedUrl(Order $order, int $days = 30): string
    {
        return URL::temporarySignedRoute(
            'orders.receipt',
            now()->addDays($days),
            ['order' => $order->id]
        );
    }

    /**
     * Message prérempli pour le partage WhatsApp.
     *
     * WhatsApp n'accepte pas de pièce jointe via un lien wa.me : on y place donc
     * le lien signé vers le PDF, que le destinataire ouvre et télécharge.
     */
    public function whatsappShareUrl(Order $order, ?string $phone = null): string
    {
        $text = sprintf(
            "Bonjour, voici le reçu de ma commande %s sur MaketuShop.\n\nMontant : %s FCFA\nDate : %s\n\nReçu PDF : %s",
            $order->order_number,
            number_format((int) round((float) $order->total_price), 0, ',', ' '),
            optional($order->paid_at)->format('d/m/Y à H:i') ?? '—',
            $this->signedUrl($order)
        );

        $base = filled($phone)
            ? 'https://wa.me/' . preg_replace('/[^\d]/', '', $phone)
            : 'https://wa.me/';

        return $base . '?text=' . rawurlencode($text);
    }

    private function data(Order $order): array
    {
        $order->loadMissing(['products.supplier', 'products.shop', 'user', 'payment']);

        $items = $order->products->map(function ($product) {
            $quantity = (int) ($product->pivot->quantity ?? 1);
            $unitPrice = (float) ($product->pivot->price ?? $product->price);

            return [
                'name' => $product->name,
                'vendor' => $product->shop?->name ?? $product->supplier?->name ?? 'MaketuShop',
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => $unitPrice * $quantity,
            ];
        })->all();

        $subtotal = array_sum(array_column($items, 'total'));

        return [
            'order' => $order,
            'items' => $items,
            'subtotal' => $subtotal,
            'total' => (float) $order->total_price,
            'paymentMethod' => $this->paymentLabel($order),
            'transactionId' => $order->payment?->transaction_id,
            'generatedAt' => now(),
        ];
    }

    private function paymentLabel(Order $order): string
    {
        return match ($order->payment_method) {
            'cinetpay' => 'CinetPay — ' . ($order->payment?->payment_method ?? 'Mobile Money / Carte'),
            'cod' => 'Paiement à la livraison',
            default => ucfirst((string) $order->payment_method),
        };
    }
}
