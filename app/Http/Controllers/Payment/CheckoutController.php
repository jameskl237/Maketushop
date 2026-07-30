<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Order\OrderService;
use App\Services\Payment\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CheckoutController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private PaymentManager $paymentManager
    ) {
        $this->middleware('auth');
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
        ]);
    }

    public function cartMethod()
    {
        return Inertia::render('Payments/Method', [
            'context' => 'cart',
            'product' => null,
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

        $order = $this->orderService->createOrder(
            Auth::id(),
            $validated,
            $items
        );

        try {
            $result = $this->paymentManager->checkoutOrder(
                $order,
                notifyUrl: route('cinetpay.webhook'),
                returnUrl: route('payments.cinetpay.callback', ['transaction_id' => $order->order_number]),
                customer: [
                    'email' => Auth::user()->email,
                    'phone' => $validated['phone_number'],
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                ]
            );

            return Inertia::location($result['payment_url']);
        } catch (\Throwable $e) {
            $order->delete();
            return back()->with('error', 'Erreur de paiement : ' . $e->getMessage());
        }
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

        $order = $this->orderService->createOrder(
            Auth::id(),
            $validated,
            $items
        );

        try {
            $result = $this->paymentManager->checkoutOrder(
                $order,
                notifyUrl: route('cinetpay.webhook'),
                returnUrl: route('payments.cinetpay.callback', ['transaction_id' => $order->order_number]),
                customer: [
                    'email' => Auth::user()->email,
                    'phone' => $validated['phone_number'],
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                ]
            );

            return Inertia::location($result['payment_url']);
        } catch (\Throwable $e) {
            $order->delete();
            return back()->with('error', 'Erreur de paiement : ' . $e->getMessage());
        }
    }

    private function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'delivery_address' => ['required', 'string', 'max:500'],
            'phone_number' => ['required', 'string', 'max:20'],
        ];
    }
}
