<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ShopSubscription;
use App\Services\CinetPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CinetPayWebhookController extends Controller
{
    public function __invoke(Request $request, CinetPayService $cinetpay)
    {
        $transactionId = $request->input('cpm_trans_id');

        if (! $transactionId) {
            Log::warning('CinetPay webhook: missing transaction ID');
            return response('Missing transaction ID', 400);
        }

        try {
            $verification = $cinetpay->verifyPayment($transactionId);

            if (! $cinetpay->isPaymentValid($verification)) {
                Log::info('CinetPay webhook: payment not validated', [
                    'transaction_id' => $transactionId,
                    'status' => $verification['status'] ?? null,
                ]);
                return response('Not validated', 200);
            }

            $this->handleVerifiedPayment($transactionId, $verification);

            return response('OK', 200);
        } catch (\Throwable $e) {
            Log::error('CinetPay webhook error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);
            return response('OK', 200);
        }
    }

    private function handleVerifiedPayment(string $transactionId, array $verification): void
    {
        $order = Order::where('order_number', $transactionId)->first();

        if ($order && ! $order->is_paid) {
            $order->update([
                'is_paid' => true,
                'payment_method' => 'cinetpay',
            ]);
            Log::info("CinetPay: order {$order->order_number} paid");
            return;
        }

        $subscription = ShopSubscription::where('transaction_id', $transactionId)->first();

        if ($subscription && $subscription->status === ShopSubscription::STATUS_ACTIVE) {
            Log::info("CinetPay: subscription {$subscription->id} already active");
        }
    }
}
