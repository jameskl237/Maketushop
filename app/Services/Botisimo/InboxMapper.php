<?php

namespace App\Services\Botisimo;

use App\Models\ShopBotMessage;
use Illuminate\Support\Str;

/**
 * Normalise les conversations et messages de Botisimo. Ni leur JSON ni celui
 * des webhooks n'étant documentés, les noms de champs possibles sont tous ici.
 */
final class InboxMapper
{
    /** Directions considérées comme venant du client ; tout le reste est sortant. */
    private const INBOUND_VALUES = ['inbound', 'incoming', 'received', 'user', 'customer', 'client'];

    /** Aplati `{event, data: {message: {...}}}` en un seul niveau. */
    public static function flatten(array $payload): array
    {
        $flat = $payload;

        foreach (['data', 'message'] as $key) {
            if (is_array($flat[$key] ?? null)) {
                $nested = $flat[$key];
                unset($flat[$key]);
                $flat = $nested + $flat;
            }
        }

        return $flat;
    }

    public static function event(array $payload): ?string
    {
        $event = $payload['event'] ?? $payload['type'] ?? null;

        return is_string($event) ? $event : null;
    }

    public static function message(array $raw, ?string $defaultDirection = null): array
    {
        $raw = self::flatten($raw);

        $direction = self::text($raw, 'direction', 'role', 'sender', 'sender_type');
        $body = self::text($raw, 'content', 'body', 'text', 'message');
        $metaId = self::text($raw, 'meta_message_id', 'wamid');
        $sentAt = BotisimoPayload::date(BotisimoPayload::get($raw, 'created_at', 'timestamp', 'sent_at'));

        $direction = $direction === null
            ? ($defaultDirection ?? ShopBotMessage::DIRECTION_OUTBOUND)
            : (in_array(Str::lower($direction), self::INBOUND_VALUES, true) ? ShopBotMessage::DIRECTION_INBOUND : ShopBotMessage::DIRECTION_OUTBOUND);

        return [
            'message_id' => self::text($raw, 'message_id', 'id') ?? $metaId
                ?? 'h:'.sha1($direction.'|'.$body.'|'.$sentAt?->toIso8601String()),
            'meta_message_id' => $metaId,
            'direction' => $direction,
            'type' => self::text($raw, 'message_type') ?? 'text',
            'body' => $body,
            'status' => self::text($raw, 'status'),
            'sent_at' => $sentAt,
        ];
    }

    /** Conversation lue dans une liste : `id` et `status` sont bien les siens. */
    public static function conversation(array $raw): array
    {
        $raw = self::flatten($raw);

        return self::conversationFields($raw, self::text($raw, 'conversation_id', 'id'), self::text($raw, 'status'));
    }

    /** Dans un webhook, `id` et `status` décrivent le message, pas la conversation. */
    public static function webhookConversation(array $payload): array
    {
        $raw = self::flatten($payload);

        return self::conversationFields($raw, self::text($raw, 'conversation_id'), null);
    }

    private static function conversationFields(array $raw, ?string $remoteId, ?string $status): array
    {
        $client = is_array($raw['client'] ?? null) ? $raw['client'] : [];

        return [
            'remote_id' => $remoteId,
            'client_phone' => self::text($client + $raw, 'phone', 'client_phone', 'customer_phone', 'from'),
            'client_name' => self::text($client + $raw, 'name', 'client_name', 'customer_name', 'contact_name'),
            'status' => $status,
            'ai_enabled' => isset($raw['ai_enabled']) ? (bool) $raw['ai_enabled'] : null,
        ];
    }

    private static function text(array $raw, string ...$keys): ?string
    {
        foreach ($keys as $key) {
            if (is_scalar($raw[$key] ?? null) && (string) $raw[$key] !== '') {
                return (string) $raw[$key];
            }
        }

        return null;
    }
}
