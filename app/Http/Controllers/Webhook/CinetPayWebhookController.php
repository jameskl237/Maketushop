<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Services\CinetPay\CinetPayWebhookHandler;
use Illuminate\Http\Request;

/**
 * Point d'entrée des notifications CinetPay (notify_url).
 *
 * CinetPay envoie un GET "à vide" pour valider l'URL lors de la configuration,
 * puis un POST à chaque changement d'état. On répond toujours 200 : un autre
 * code déclenche des relances côté CinetPay.
 */
class CinetPayWebhookController extends Controller
{
    public function __construct(
        private CinetPayWebhookHandler $handler
    ) {}

    public function __invoke(Request $request)
    {
        // Test de disponibilité de l'URL lors du paramétrage du service.
        if ($request->isMethod('get')) {
            return response('OK', 200);
        }

        $payload = $request->all();

        $transactionId = $payload['cpm_trans_id']
            ?? $payload['transaction_id']
            ?? null;

        if (!$transactionId) {
            return response()->json(['status' => 'error', 'message' => 'Missing transaction ID'], 200);
        }

        $result = $this->handler->handle(
            $payload,
            $request->headers->all(),
            $request->header('x-token')
        );

        return response()->json(['status' => $result['success'] ? 'success' : 'error'], 200);
    }
}
