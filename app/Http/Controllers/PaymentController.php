<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\CinetPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PaymentController extends Controller
{
    public function initialize(Request $request, Product $product, CinetPayService $cinetpay)
    {
        if (! Auth::check()) {
            return redirect()->route('login')->with('error', 'Veuillez vous connecter pour continuer votre achat.');
        }

        $user = Auth::user();
        $validated = $request->validate($this->checkoutValidationRules());

        $items = [[
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $this->productPrice($product),
        ]];

        $reference = $this->generateReference();
        $order = $this->createPendingOrder($reference, (int) $user->id, $validated, $items);

        try {
            $channel = $validated['payment_channel'] ?? null;

            if ($this->isCinetpayChannel($channel)) {
                return $this->initializeWithCinetpay($cinetpay, $order, $user, $product->name, $items, $validated);
            }

            throw new \RuntimeException('Mode de paiement non supporté.');
        } catch (\Throwable $e) {
            $order->delete();

            return back()->with('error', 'Une erreur est survenue lors de l\'initialisation du paiement : '.$e->getMessage());
        }
    }

    /**
     * Le paiement en ligne est-il activé ?
     */
    private function payOnlineEnabled(): bool
    {
        return app(CinetPayService::class)->isConfigured();
    }

    public function productMethod(Product $product)
    {
        if (! $this->payOnlineEnabled()) {
            return redirect()->route('payments.unavailable');
        }

        $product->load([
            'shop:id,name',
            'medias:id,product_id,url,type,is_principal',
        ]);

        $image = $product->medias
            ->where('type', 'image')
            ->sortByDesc('is_principal')
            ->first();

        return Inertia::render('Payments/Method', [
            'context' => 'product',
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $this->productPrice($product),
                'image' => $image?->full_url,
                'shop_name' => $product->shop?->name,
            ],
            'methods' => $this->paymentMethods(),
        ]);
    }

    public function cartMethod()
    {
        if (! $this->payOnlineEnabled()) {
            return redirect()->route('payments.unavailable');
        }

        return Inertia::render('Payments/Method', [
            'context' => 'cart',
            'product' => null,
            'methods' => $this->paymentMethods(),
        ]);
    }

    public function checkoutCart(Request $request, CinetPayService $cinetpay)
    {
        if (! Auth::check()) {
            return redirect()->route('login')->with('error', 'Veuillez vous connecter pour continuer votre achat.');
        }

        $user = Auth::user();
        $validated = $request->validate(array_merge($this->checkoutValidationRules(), [
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]));

        $items = collect($validated['items'])
            ->groupBy('id')
            ->map(fn ($rows, $productId): array => [
                'id' => (int) $productId,
                'quantity' => $rows->sum('quantity'),
            ])
            ->values();

        $products = Product::query()
            ->whereIn('id', $items->pluck('id')->unique()->values())
            ->get()
            ->keyBy('id');

        $paymentItems = $items
            ->map(function (array $item) use ($products): array {
                $product = $products->get($item['id']);

                return [
                    'product_id' => $product->id,
                    'quantity' => (int) $item['quantity'],
                    'price' => $this->productPrice($product),
                ];
            })
            ->values()
            ->all();

        $reference = $this->generateReference();
        $order = $this->createPendingOrder($reference, (int) $user->id, $validated, $paymentItems);

        try {
            $channel = $validated['payment_channel'] ?? null;

            if ($this->isCinetpayChannel($channel)) {
                return $this->initializeWithCinetpay(
                    $cinetpay, $order, $user,
                    count($paymentItems).' produit(s)',
                    $paymentItems, $validated
                );
            }

            throw new \RuntimeException('Mode de paiement non supporté.');
        } catch (\Throwable $e) {
            $order->delete();

            return back()->with('error', 'Une erreur est survenue lors de l\'initialisation du paiement : '.$e->getMessage());
        }
    }

    public function callback(Request $request, CinetPayService $cinetpay)
    {
        $reference = $request->query('reference');

        if (! $reference) {
            return redirect()->route('products.index')->with('error', 'Référence de paiement manquante.');
        }

        return $this->handleCinetpayCallback($cinetpay, $reference);
    }

    private function checkoutValidationRules(): array
    {
        return [
            'payment_channel' => ['required', 'string', 'in:cinetpay-momo,cinetpay-card,cinetpay-all'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'delivery_address' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20'],
        ];
    }

    private function generateReference(): string
    {
        do {
            $reference = 'ORD-'.strtoupper(Str::random(12));
        } while (Order::where('order_number', $reference)->exists());

        return $reference;
    }

    private function createPendingOrder(string $reference, int $userId, array $delivery, array $items): Order
    {
        return DB::transaction(function () use ($reference, $userId, $delivery, $items): Order {
            $order = Order::create([
                'order_number' => $reference,
                'user_id' => $userId,
                'customer_first_name' => $delivery['first_name'],
                'customer_last_name' => $delivery['last_name'],
                'delivery_address' => $delivery['delivery_address'],
                'phone_number' => $delivery['phone_number'],
                'total_products' => collect($items)->sum('quantity'),
                'total_price' => $this->itemsTotal($items),
                'status' => Order::STATUS_PENDING,
                'is_paid' => false,
                'payment_method' => $delivery['payment_channel'] ?? null,
            ]);

            foreach ($items as $item) {
                $order->products()->attach($item['product_id'], [
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }

            return $order;
        });
    }

    private function productPrice(Product $product): float
    {
        return (float) $product->price;
    }

    private function itemsTotal(array $items): float
    {
        return collect($items)->sum(fn (array $item): float => (float) $item['price'] * (int) $item['quantity']);
    }

    private function isCinetpayChannel(?string $channel): bool
    {
        return $channel !== null && str_starts_with($channel, 'cinetpay');
    }

    private function initializeWithCinetpay(
        CinetPayService $cinetpay,
        Order $order,
        \App\Models\User $user,
        string $productLabel,
        array $items,
        array $delivery,
    ) {
        $channel = $delivery['payment_channel'] ?? 'cinetpay-all';

        $channelsMap = [
            'cinetpay-momo' => 'MOBILE',
            'cinetpay-card' => 'CARD',
            'cinetpay-all' => 'ALL',
        ];

        $payment = $cinetpay->initializePayment([
            'transaction_id' => $order->order_number,
            'amount' => (int) round((float) $order->total_price),
            'description' => 'Achat ' . $productLabel . ' - MaketuShop',
            'notify_url' => route('cinetpay.webhook'),
            'return_url' => route('payments.callback', ['reference' => $order->order_number, 'provider' => 'cinetpay']),
            'channels' => $channelsMap[$channel] ?? 'ALL',
            'customer' => [
                'name' => $delivery['first_name'] ?? '',
                'surname' => $delivery['last_name'] ?? '',
                'email' => $user->email ?? '',
                'phone_number' => $delivery['phone_number'] ?? $user->phone ?? '+2250000000000',
                'address' => $delivery['delivery_address'] ?? '',
                'city' => 'Abidjan',
                'country' => 'CI',
            ],
            'metadata' => [
                'order_id' => $order->order_number,
                'user_id' => $user->id,
                'type' => 'product_purchase',
            ],
        ]);

        $order->update(['payment_method' => 'cinetpay']);

        return Inertia::location($payment['payment_url']);
    }

    private function handleCinetpayCallback(CinetPayService $cinetpay, string $transactionId)
    {
        try {
            $verification = $cinetpay->verifyPayment($transactionId);

            $order = Order::where('order_number', $transactionId)->first();

            if (! $order) {
                return redirect()->route('products.index')->with('error', 'Commande introuvable pour ce paiement.');
            }

            if ($cinetpay->isPaymentValid($verification)) {
                $order->update(['is_paid' => true]);

                return redirect()->route('user.dashboard')->with('success', 'Votre paiement CinetPay a été effectué avec succès !');
            }

            return redirect()->route('products.index')->with('error', 'Le paiement CinetPay n\'a pas été confirmé.');
        } catch (\Throwable $e) {
            return redirect()->route('products.index')->with('error', 'Erreur CinetPay : ' . $e->getMessage());
        }
    }

    private function paymentMethods(): array
    {
        $cinetpay = app(CinetPayService::class);

        if (!$cinetpay->isConfigured()) {
            return [];
        }

        return [
            [
                'key' => 'cinetpay-momo',
                'name' => 'Mobile Money (UEMOA)',
                'description' => 'Orange Money, MTN MoMo, Moov Money, Flooz, T-Money (XOF).',
                'channel' => 'cinetpay-momo',
                'image' => '/images/payments/cinetpay.svg',
                'provider' => 'cinetpay',
            ],
            [
                'key' => 'cinetpay-card',
                'name' => 'Carte bancaire (UEMOA)',
                'description' => 'Visa, MasterCard, American Express (XOF).',
                'channel' => 'cinetpay-card',
                'image' => '/images/payments/cinetpay.svg',
                'provider' => 'cinetpay',
            ],
            [
                'key' => 'cinetpay-all',
                'name' => 'CinetPay (Tous moyens)',
                'description' => 'Mobile Money, Cartes, E-wallet (XOF).',
                'channel' => 'cinetpay-all',
                'image' => '/images/payments/cinetpay.svg',
                'provider' => 'cinetpay',
            ],
        ];
    }
}
