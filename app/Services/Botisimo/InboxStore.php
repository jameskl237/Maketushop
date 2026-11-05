<?php

namespace App\Services\Botisimo;

use App\Models\ShopBot;
use App\Models\ShopBotConversation;
use App\Models\ShopBotMessage;
use App\Notifications\AssistantHumanTransferNotification;
use App\Services\Botisimo\Exceptions\BotisimoException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/** Copie locale des conversations, alimentée par les webhooks et les lectures API. */
class InboxStore
{
    private const REFRESH_INTERVAL_SECONDS = 30;

    private const TRANSFER_ALERT_COOLDOWN_SECONDS = 3600;

    private const CONVERSATION_PAGE_SIZE = 100;

    private const CONVERSATION_MAX_PAGES = 3;

    /** Un statut plus ancien arrivé en retard ne doit pas écraser le plus récent. */
    private const STATUS_RANK = ['sent' => 1, 'delivered' => 2, 'read' => 3];

    public function __construct(private BotisimoClient $client) {}

    public function recordInbound(ShopBot $bot, array $payload): void
    {
        $conversation = $this->upsertConversation($bot, InboxMapper::webhookConversation($payload));

        if ($conversation === null) {
            Log::warning('Webhook Botisimo sans conversation ni numéro', ['shop_bot_id' => $bot->id]);

            return;
        }

        $data = InboxMapper::message($payload, ShopBotMessage::DIRECTION_INBOUND);
        $data['direction'] = ShopBotMessage::DIRECTION_INBOUND;

        if (! $this->upsertMessage($conversation, $data)) {
            return;
        }

        $conversation->increment('unread_count');
        $this->refreshSummary($conversation);
        $this->alertOnTransferKeyword($bot, $conversation, (string) $data['body']);
    }

    public function recordStatus(ShopBot $bot, array $payload): void
    {
        $data = InboxMapper::message($payload);

        $message = ShopBotMessage::query()
            ->whereHas('conversation', fn ($query) => $query->where('shop_bot_id', $bot->id))
            ->where(fn ($query) => $query
                ->where('meta_message_id', $data['meta_message_id'] ?? '')
                ->orWhere('message_id', $data['message_id']))
            ->first();

        if ($message === null || $data['status'] === null || ! $this->isNewerStatus($message->status, $data['status'])) {
            return;
        }

        $message->update(['status' => $data['status']]);
    }

    private function isNewerStatus(?string $current, string $next): bool
    {
        if ($current === 'failed' || ! isset(self::STATUS_RANK[$next])) {
            return $current !== 'failed';
        }

        return self::STATUS_RANK[$next] > (self::STATUS_RANK[$current] ?? 0);
    }

    /**
     * Sans identifiant distant, la conversation est retrouvée par son numéro :
     * l'identifiant provisoire `phone:` est remplacé dès que le vrai est connu.
     */
    public function upsertConversation(ShopBot $bot, array $data): ?ShopBotConversation
    {
        $phone = $data['client_phone'];
        $remoteId = $data['remote_id'];

        if ($remoteId === null && $phone === null) {
            return null;
        }

        $conversation = $bot->conversations()
            ->where('botisimo_conversation_id', $remoteId ?? "phone:{$phone}")
            ->first()
            ?? ($phone !== null ? $bot->conversations()->where('client_phone', $phone)->orderByDesc('id')->first() : null)
            ?? $bot->conversations()->make(['channel' => $bot->channel]);

        $conversation->fill(array_filter([
            'botisimo_conversation_id' => $remoteId ?? $conversation->botisimo_conversation_id ?? "phone:{$phone}",
            'client_phone' => $phone,
            'client_name' => $data['client_name'],
            'status' => $data['status'],
            'ai_enabled' => $data['ai_enabled'],
        ], fn ($value) => $value !== null))->save();

        return $conversation;
    }

    /** @return bool true si le message est nouveau */
    public function upsertMessage(ShopBotConversation $conversation, array $data): bool
    {
        $existing = $conversation->messages()->where('message_id', $data['message_id'])->first()
            ?? $this->unmatchedOutbound($conversation, $data);

        if ($existing !== null) {
            $existing->fill(array_filter(
                ['message_id' => $data['message_id'], 'meta_message_id' => $data['meta_message_id']]
            ))->save();

            return false;
        }

        $conversation->messages()->create(['sent_at' => $data['sent_at'] ?? now()] + $data);

        return true;
    }

    /** Une réponse du vendeur dont l'envoi n'a renvoyé aucun identifiant. */
    private function unmatchedOutbound(ShopBotConversation $conversation, array $data): ?ShopBotMessage
    {
        if ($data['direction'] !== ShopBotMessage::DIRECTION_OUTBOUND) {
            return null;
        }

        return $conversation->messages()
            ->whereNull('message_id')
            ->where('direction', ShopBotMessage::DIRECTION_OUTBOUND)
            ->where('body', $data['body'])
            ->first();
    }

    public function refreshSummary(ShopBotConversation $conversation): void
    {
        $last = $conversation->messages()->orderByDesc('sent_at')->orderByDesc('id')->first();
        $lastInbound = $conversation->messages()
            ->where('direction', ShopBotMessage::DIRECTION_INBOUND)
            ->max('sent_at');

        $conversation->update([
            'last_message_preview' => Str::limit((string) $last?->body, 120),
            'last_message_at' => $last?->sent_at,
            'last_inbound_at' => $lastInbound,
        ]);
    }

    private function alertOnTransferKeyword(ShopBot $bot, ShopBotConversation $conversation, string $body): void
    {
        $text = Str::lower(Str::ascii($body));

        $matched = collect($bot->human_transfer_keywords ?? [])
            ->first(fn ($keyword) => filled($keyword) && str_contains($text, Str::lower(Str::ascii($keyword))));

        if ($matched !== null && Cache::add("botisimo:transfer:{$conversation->id}", 1, self::TRANSFER_ALERT_COOLDOWN_SECONDS)) {
            $bot->shop?->user?->notify(new AssistantHumanTransferNotification($bot, $conversation));
        }
    }

    /**
     * Les réponses de l'IA n'arrivent par aucun webhook : on les lit à l'ouverture
     * et pendant le polling, au plus une fois par intervalle. En cas d'échec, la
     * page affiche simplement la copie locale.
     */
    public function refreshMessages(ShopBotConversation $conversation): void
    {
        if (str_starts_with($conversation->botisimo_conversation_id, 'phone:')
            || ! Cache::add("botisimo:conversation:{$conversation->id}", 1, self::REFRESH_INTERVAL_SECONDS)) {
            return;
        }

        try {
            $response = $this->client->listConversationMessages($conversation->botisimo_conversation_id);
        } catch (BotisimoException) {
            return;
        }

        $rows = BotisimoPayload::list($response, 'messages', 'items', 'data') ?? [];

        foreach ($rows as $row) {
            if (is_array($row)) {
                $this->upsertMessage($conversation, InboxMapper::message($row));
            }
        }

        $this->refreshSummary($conversation);
    }

    /** Filet de sécurité : Botisimo ne rejoue jamais un webhook perdu. */
    public function reconcileConversations(ShopBot $bot): int
    {
        $seen = 0;
        $known = [];

        for ($page = 0; $page < self::CONVERSATION_MAX_PAGES; $page++) {
            $response = $this->client->listConversations([
                'bot_id' => $bot->botisimo_bot_id,
                'limit' => self::CONVERSATION_PAGE_SIZE,
                'offset' => $page * self::CONVERSATION_PAGE_SIZE,
            ]);
            $rows = BotisimoPayload::list($response, 'conversations', 'items', 'data');

            if ($rows === null) {
                Log::warning('Liste de conversations Botisimo illisible', ['shop_bot_id' => $bot->id]);

                break;
            }

            $countBefore = count($known);

            foreach ($rows as $row) {
                $data = is_array($row) ? InboxMapper::conversation($row) : null;

                if ($data !== null && $data['remote_id'] !== null && $this->upsertConversation($bot, $data) !== null) {
                    $known[$data['remote_id']] = true;
                    $seen++;
                }
            }

            if (count($rows) < self::CONVERSATION_PAGE_SIZE || count($known) === $countBefore) {
                break;
            }
        }

        return $seen;
    }
}
