<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\Botisimo\ProcessBotisimoWebhook;
use App\Models\ShopBot;
use App\Models\ShopBotWebhookEvent;
use App\Services\Botisimo\InboxMapper;
use Illuminate\Http\Request;

/**
 * Botisimo exige un 200 en moins de 5 secondes et ne rejoue jamais un envoi :
 * on vérifie, on enregistre, et tout le traitement part dans un job.
 */
class BotisimoWebhookController extends Controller
{
    public function __invoke(Request $request, ShopBot $shopBot)
    {
        $raw = $request->getContent();

        if (! $this->hasValidSignature($raw, (string) $request->header('X-Botisimo-Signature'), $shopBot)) {
            return response()->json(['message' => 'Signature invalide'], 401);
        }

        $payload = json_decode($raw, true);

        if (! is_array($payload)) {
            return response()->json(['message' => 'Corps invalide'], 400);
        }

        $event = ShopBotWebhookEvent::createOrFirst(
            ['shop_bot_id' => $shopBot->id, 'event_key' => $this->eventKey($payload, $raw)],
            ['event' => (string) InboxMapper::event($payload), 'payload' => $payload],
        );

        if ($event->wasRecentlyCreated) {
            ProcessBotisimoWebhook::dispatch($event->id);
        }

        return response()->json(['received' => true]);
    }

    private function hasValidSignature(string $raw, string $header, ShopBot $bot): bool
    {
        $secret = $bot->webhook_secret;

        return filled($secret)
            && hash_equals('sha256='.hash_hmac('sha256', $raw, $secret), $header);
    }

    /** Un même message passe par plusieurs statuts : le statut fait partie de la clé. */
    private function eventKey(array $payload, string $raw): string
    {
        $flat = InboxMapper::flatten($payload);
        $id = $flat['meta_message_id'] ?? $flat['message_id'] ?? $flat['id'] ?? null;

        if (! is_scalar($id) || $id === '') {
            return 'raw:'.hash('sha256', $raw);
        }

        $status = is_scalar($flat['status'] ?? null) ? ":{$flat['status']}" : '';

        return substr(InboxMapper::event($payload).":{$id}{$status}", 0, 191);
    }
}
