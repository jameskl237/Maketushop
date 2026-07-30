<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Services\CinetPay\CinetPayWebhookHandler;
use Illuminate\Http\Request;

class CinetPayWebhookController extends Controller
{
    public function __construct(
        private CinetPayWebhookHandler $handler
    ) {}

    public function __invoke(Request $request)
    {
        $payload = $request->all();
        $headers = $request->headers->all();

        if (empty($payload['cpm_trans_id'])) {
            $transactionId = $request->input('transaction_id') ?? $request->input('cpm_trans_id');
            if (!$transactionId) {
                return response()->json(['status' => 'error', 'message' => 'Missing transaction ID'], 400);
            }
            $payload['cpm_trans_id'] = $transactionId;
        }

        $result = $this->handler->handle($payload, $headers);

        return response()->json(['status' => $result['success'] ? 'success' : 'error'], 200);
    }
}
