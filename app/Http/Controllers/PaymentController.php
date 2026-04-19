<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use NotchPay\NotchPay;
use NotchPay\Payment;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct()
    {
        NotchPay::setApiKey(config('services.notchpay.secret_key'));
    }

    public function initialize(Request $request, Product $product)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Veuillez vous connecter pour continuer votre achat.');
        }

        $user = Auth::user();

        // Create a temporary order or just use product info
        $reference = 'ORD-' . strtoupper(Str::random(10));

        try {
            $payment = Payment::initialize([
                'amount' => (int) $product->price,
                'email' => $user->email,
                'currency' => 'XAF', // Assuming XAF as default
                'reference' => $reference,
                'callback' => route('payments.callback'),
                'description' => 'Achat de ' . $product->name . ' sur MaketuShop',
                'metadata' => [
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                ]
            ]);

            return redirect()->away($payment->authorization_url);
        } catch (\Exception $e) {
            return back()->with('error', 'Une erreur est survenue lors de l\'initialisation du paiement : ' . $e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        $reference = $request->query('reference');

        if (!$reference) {
            return redirect()->route('products.index')->with('error', 'Référence de paiement manquante.');
        }

        try {
            $payment = Payment::verify($reference);

            if ($payment->transaction->status === 'complete') {
                // Handle successful payment
                // Create order here if not already created
                $userId = $payment->transaction->metadata->user_id ?? null;
                $productId = $payment->transaction->metadata->product_id ?? null;

                if ($userId && $productId) {
                    $product = Product::find($productId);
                    
                    $order = Order::create([
                        'order_number' => $reference,
                        'user_id' => $userId,
                        'total_products' => 1,
                        'total_price' => $payment->transaction->amount,
                    ]);

                    $order->products()->attach($productId, [
                        'quantity' => 1,
                        'price' => $product->price,
                    ]);

                    return redirect()->route('dashboard')->with('success', 'Votre paiement a été effectué avec succès ! Votre commande est en cours de traitement.');
                }
            }

            return redirect()->route('products.index')->with('error', 'Le paiement n\'a pas pu être complété.');
        } catch (\Exception $e) {
            return redirect()->route('products.index')->with('error', 'Erreur de vérification du paiement.');
        }
    }
}
