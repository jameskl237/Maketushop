<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\CinetPay\CinetPayService;
use App\Services\Order\OrderService;
use App\Services\Payment\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CheckoutController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private PaymentManager $paymentManager
    ) {
    }

    public function productMethod(Product $product)
    {
        $product->load(['shop:id,name', 'medias:id,product_id,url,type,is_principal']);

        $image = $product->medias
            ->where('type', 'image')
            ->sortByDesc('is_principal')
            ->first();

        return Inertia::render('Payments/Method', [
            'context' => 'product',
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (float) $product->price,
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

    public function checkoutProduct(Request $request, Product $product)
    {
        $validated = $request->validate($this->rules());

        $items = [[
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => (float) $product->price,
        ]];

        $channel = $validated['payment_channel'] ?? 'cod';

        if ($channel === 'cod') {
            return $this->handleCodOrder(Auth::id(), $validated, $items);
        }

        return $this->handleOnlinePayment(Auth::id(), $validated, $items);
    }

    public function checkoutCart(Request $request)
    {
        $validated = $request->validate(array_merge($this->rules(), [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]));

        $products = Product::whereIn('id', collect($validated['items'])->pluck('id'))->get()->keyBy('id');

        $items = collect($validated['items'])
            ->groupBy('id')
            ->map(fn ($rows, $productId) => [
                'product_id' => (int) $productId,
                'quantity' => $rows->sum('quantity'),
                'price' => (float) ($products->get((int) $productId)?->price ?? 0),
            ])
            ->values()
            ->all();

        $channel = $validated['payment_channel'] ?? 'cod';

        if ($channel === 'cod') {
            return $this->handleCodOrder(Auth::id(), $validated, $items);
        }

        return $this->handleOnlinePayment(Auth::id(), $validated, $items);
    }

    private function handleCodOrder(int $userId, array $validated, array $items)
    {
        $order = $this->orderService->createOrder(
            $userId,
            $validated,
            $items,
            paymentMethod: 'cod'
        );

        return redirect()->route('home')->with('success', 'Votre commande a été enregistrée. Vous paierez à la livraison.');
    }

    private function handleOnlinePayment(int $userId, array $validated, array $items)
    {
        $order = $this->orderService->createOrder(
            $userId,
            $validated,
            $items,
            paymentMethod: 'online'
        );

        $transactionId = app(CinetPayService::class)->generateTransactionId();

        try {
            $result = $this->paymentManager->checkoutOrder(
                $order,
                notifyUrl: route('cinetpay.webhook'),
                returnUrl: route('payments.cinetpay.callback', ['transaction_id' => $transactionId]),
                customer: [
                    'email' => Auth::user()->email,
                    'phone' => $this->formatPhone($validated['phone_number']),
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                ],
                transactionId: $transactionId,
            );

            return Inertia::location($result['payment_url']);
        } catch (\Throwable $e) {
            $order->delete();
            return back()->with('error', 'Erreur de paiement : ' . $e->getMessage());
        }
    }

    private function formatPhone(string $phone): string
    {
        $digits = preg_replace('/[^\d]/', '', $phone);

        if (strlen($digits) >= 11 && str_starts_with($digits, '225')) {
            return '+' . $digits;
        }

        if (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return '+225' . $digits;
    }

    private function paymentMethods(): array
    {
        return [
            [
                'key' => 'online',
                'name' => 'Paiement en ligne',
                'channel' => 'cinetpay',
                'description' => 'Orange Money, MTN MoMo, Moov Money, Flooz, T-Money, Carte bancaire',
                'image' => '/images/payments/cinetpay.svg',
            ],
            [
                'key' => 'cod',
                'name' => 'Paiement à la livraison',
                'channel' => 'cod',
                'description' => 'Vous payez en espèces à la réception de votre commande',
                'image' => '/images/payments/cash-delivery.svg',
            ],
        ];
    }

    private function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'delivery_address' => ['required', 'string', 'max:500'],
            'phone_number' => ['required', 'string', 'max:20'],
            'payment_channel' => ['nullable', 'string', 'in:cinetpay,cod'],
        ];
    }
}
