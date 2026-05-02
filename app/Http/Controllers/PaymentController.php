<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use NotchPay\NotchPay;
use NotchPay\Payment;

class PaymentController extends Controller
{
    public function __construct()
    {
        NotchPay::setApiKey(config('services.notchpay.public_key'));
        NotchPay::setPrivateKey(config('services.notchpay.secret_key'));
    }

    public function initialize(Request $request, Product $product)
    {
        if (! Auth::check()) {
            return redirect()->route('login')->with('error', 'Veuillez vous connecter pour continuer votre achat.');
        }

        $user = Auth::user();
        $validated = $request->validate([
            'payment_channel' => ['required', 'string', 'in:cm.mtn,cm.orange'],
        ]);

        // Create a temporary order or just use product info
        $reference = 'ORD-'.strtoupper(Str::random(10));

        try {
            $payload = $this->withPaymentChannel([
                'amount' => (int) $this->productPrice($product),
                'email' => $user->email,
                'currency' => 'XAF',
                'reference' => $reference,
                'callback' => route('payments.callback'),
                'description' => 'Achat de '.$product->name.' sur MaketuShop',
                'customer' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
                'metadata' => [
                    'user_id' => $user->id,
                    'items' => [
                        [
                            'product_id' => $product->id,
                            'quantity' => 1,
                            'price' => $this->productPrice($product),
                        ],
                    ],
                ],
            ], $validated['payment_channel'] ?? null);

            $payment = Payment::initialize($payload);

            return Inertia::location($payment->authorization_url);
        } catch (\Exception $e) {
            return back()->with('error', 'Une erreur est survenue lors de l\'initialisation du paiement : '.$e->getMessage());
        }
    }

    public function productMethod(Product $product)
    {
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
        return Inertia::render('Payments/Method', [
            'context' => 'cart',
            'product' => null,
            'methods' => $this->paymentMethods(),
        ]);
    }

    public function checkoutCart(Request $request)
    {
        if (! Auth::check()) {
            return redirect()->route('login')->with('error', 'Veuillez vous connecter pour continuer votre achat.');
        }

        $user = Auth::user();
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'payment_channel' => ['required', 'string', 'in:cm.mtn,cm.orange'],
        ]);

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

        $totalAmount = $this->itemsTotal($paymentItems);
        $reference = 'ORD-'.strtoupper(Str::random(10));

        try {
            $payload = $this->withPaymentChannel([
                'amount' => (int) $totalAmount,
                'email' => $user->email,
                'currency' => 'XAF',
                'reference' => $reference,
                'callback' => route('payments.callback'),
                'description' => 'Achat de '.count($paymentItems).' produits sur MaketuShop',
                'customer' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
                'metadata' => [
                    'user_id' => $user->id,
                    'items' => $paymentItems,
                ],
            ], $validated['payment_channel'] ?? null);

            $payment = Payment::initialize($payload);

            return Inertia::location($payment->authorization_url);
        } catch (\Exception $e) {
            return back()->with('error', 'Une erreur est survenue lors de l\'initialisation du paiement : '.$e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        $reference = $request->query('reference');

        if (! $reference) {
            return redirect()->route('products.index')->with('error', 'Référence de paiement manquante.');
        }

        try {
            $payment = Payment::verify($reference);

            if ($payment->transaction->status === 'complete') {
                $userId = $payment->transaction->metadata->user_id ?? null;
                $items = $this->normalizeItems($payment->transaction->metadata->items ?? []);

                if ($userId && ! empty($items)) {
                    DB::transaction(function () use ($reference, $userId, $items): void {
                        $order = Order::create([
                            'order_number' => $reference,
                            'user_id' => $userId,
                            'total_products' => collect($items)->sum('quantity'),
                            'total_price' => $this->itemsTotal($items),
                            'status' => Order::STATUS_PENDING,
                            'is_paid' => true,
                        ]);

                        foreach ($items as $item) {
                            $order->products()->attach($item['product_id'], [
                                'quantity' => $item['quantity'],
                                'price' => $item['price'],
                            ]);
                        }
                    });

                    return redirect()->route('user.dashboard')->with('success', 'Votre paiement a été effectué avec succès ! Votre commande est en cours de traitement.');
                }
            }

            return redirect()->route('products.index')->with('error', 'Le paiement n\'a pas pu être complété.');
        } catch (\Exception $e) {
            return redirect()->route('products.index')->with('error', 'Erreur de vérification du paiement : '.$e->getMessage());
        }
    }

    private function productPrice(Product $product): float
    {
        return (float) $product->price;
    }

    private function itemsTotal(array $items): float
    {
        return collect($items)->sum(fn (array $item): float => (float) $item['price'] * (int) $item['quantity']);
    }

    private function normalizeItems(mixed $items): array
    {
        return collect(json_decode(json_encode($items), true) ?: [])
            ->map(fn (array $item): array => [
                'product_id' => (int) ($item['product_id'] ?? $item['id']),
                'quantity' => (int) $item['quantity'],
                'price' => (float) $item['price'],
            ])
            ->filter(fn (array $item): bool => $item['product_id'] > 0 && $item['quantity'] > 0)
            ->values()
            ->all();
    }

    private function withPaymentChannel(array $payload, ?string $channel): array
    {
        if ($channel) {
            return array_merge($payload, [
                'locked_channel' => $channel,
                'locked_country' => 'CM',
                'locked_currency' => 'XAF',
            ]);
        }

        return array_merge($payload, [
            'channels' => ['mobile_money', 'card'],
        ]);
    }

    private function paymentMethods(): array
    {
        return [
            [
                'key' => 'mtn',
                'name' => 'MTN MoMo',
                'description' => 'Paiement Mobile Money MTN Cameroun.',
                'channel' => 'cm.mtn',
                'image' => '/images/payments/mtn-momo.svg',
            ],
            [
                'key' => 'orange',
                'name' => 'Orange Money',
                'description' => 'Paiement Orange Money Cameroun.',
                'channel' => 'cm.orange',
                'image' => '/images/payments/orange-money.svg',
            ],
        ];
    }
}
