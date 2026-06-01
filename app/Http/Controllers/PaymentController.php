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
    public function initialize(Request $request, Product $product)
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
            $this->configureNotchPay();

            $payment = Payment::initialize($this->buildPaymentPayload(
                reference: $reference,
                amount: $order->total_price,
                email: $user->email,
                customerName: trim($validated['first_name'].' '.$validated['last_name']),
                phoneNumber: $validated['phone_number'],
                description: 'Achat de '.$product->name.' sur MaketuShop',
                items: $items,
                userId: (int) $user->id,
                delivery: $validated,
                channel: $validated['payment_channel'] ?? null,
            ));

            return Inertia::location($payment->authorization_url);
        } catch (\Throwable $e) {
            $order->delete();

            return back()->with('error', 'Une erreur est survenue lors de l\'initialisation du paiement : '.$e->getMessage());
        }
    }

    /**
     * Le paiement en ligne est-il activé ? (clés NotchPay + flag explicite)
     */
    private function payOnlineEnabled(): bool
    {
        return filled(config('services.notchpay.public_key'))
            && filled(config('services.notchpay.secret_key'))
            && (bool) env('PAY_ONLINE_ENABLED', false);
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

    public function checkoutCart(Request $request)
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
            $this->configureNotchPay();

            $payment = Payment::initialize($this->buildPaymentPayload(
                reference: $reference,
                amount: $order->total_price,
                email: $user->email,
                customerName: trim($validated['first_name'].' '.$validated['last_name']),
                phoneNumber: $validated['phone_number'],
                description: 'Achat de '.count($paymentItems).' produit(s) sur MaketuShop',
                items: $paymentItems,
                userId: (int) $user->id,
                delivery: $validated,
                channel: $validated['payment_channel'] ?? null,
            ));

            return Inertia::location($payment->authorization_url);
        } catch (\Throwable $e) {
            $order->delete();

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
            $this->configureNotchPay();
            $payment = Payment::verify($reference);
            $transaction = $payment->transaction ?? $payment;
            $status = $transaction->status ?? null;

            $order = Order::where('order_number', $reference)->first();

            if (! $order) {
                return redirect()->route('products.index')->with('error', 'Commande introuvable pour ce paiement.');
            }

            if ($status === 'complete') {
                $order->update(['is_paid' => true]);

                return redirect()->route('user.dashboard')->with('success', 'Votre paiement a été effectué avec succès ! Votre commande est en cours de traitement.');
            }

            return redirect()->route('products.index')->with('error', 'Le paiement n\'a pas pu être complété. Statut actuel : '.($status ?: 'inconnu').'.');
        } catch (\Throwable $e) {
            return redirect()->route('products.index')->with('error', 'Erreur de vérification du paiement : '.$e->getMessage());
        }
    }

    private function checkoutValidationRules(): array
    {
        return [
            'payment_channel' => ['required', 'string', 'in:cm.mtn,cm.orange'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'delivery_address' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20'],
        ];
    }

    private function configureNotchPay(): void
    {
        $apiKey = config('services.notchpay.public_key');
        $privateKey = config('services.notchpay.secret_key');

        if (! filled($apiKey)) {
            throw new \RuntimeException('La clé publique NotchPay est manquante. Configurez NOTCHPAY_PUBLIC_KEY ou NOTCHPAY_API_KEY dans le fichier .env.');
        }

        NotchPay::setApiKey($apiKey);

        if (filled($privateKey)) {
            NotchPay::setPrivateKey($privateKey);
        }
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

    private function buildPaymentPayload(
        string $reference,
        float|string $amount,
        string $email,
        string $customerName,
        string $phoneNumber,
        string $description,
        array $items,
        int $userId,
        array $delivery,
        ?string $channel = null,
    ): array {
        return $this->withPaymentChannel([
            'amount' => (int) round((float) $amount),
            'email' => $email,
            'phone' => $phoneNumber,
            'currency' => 'XAF',
            'reference' => $reference,
            'callback' => route('payments.callback'),
            'description' => $description,
            'customer' => [
                'name' => $customerName,
                'email' => $email,
                'phone' => $phoneNumber,
            ],
            'metadata' => [
                'order_id' => $reference,
                'user_id' => $userId,
                'customer_first_name' => $delivery['first_name'],
                'customer_last_name' => $delivery['last_name'],
                'delivery_address' => $delivery['delivery_address'],
                'phone_number' => $phoneNumber,
                'items' => $items,
            ],
        ], $channel);
    }

    private function productPrice(Product $product): float
    {
        return (float) $product->price;
    }

    private function itemsTotal(array $items): float
    {
        return collect($items)->sum(fn (array $item): float => (float) $item['price'] * (int) $item['quantity']);
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
                'description' => 'Paiement via MTN Mobile Money Cameroun sur NotchPay.',
                'channel' => 'cm.mtn',
                'image' => '/images/payments/mtn-momo.svg',
            ],
            [
                'key' => 'orange',
                'name' => 'Orange Money',
                'description' => 'Paiement via Orange Money Cameroun sur NotchPay.',
                'channel' => 'cm.orange',
                'image' => '/images/payments/orange-money.svg',
            ],
        ];
    }
}
