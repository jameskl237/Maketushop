<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\ReplyConversationRequest;
use App\Http\Requests\Vendor\SendConversationTemplateRequest;
use App\Http\Requests\Vendor\ToggleConversationAiRequest;
use App\Http\Resources\ShopBotConversationResource;
use App\Http\Resources\ShopBotMessageResource;
use App\Http\Resources\ShopBotTemplateResource;
use App\Models\Shop;
use App\Models\ShopBotConversation;
use App\Models\ShopBotMessage;
use App\Models\ShopBotTemplate;
use App\Services\Botisimo\BotisimoClient;
use App\Services\Botisimo\BotisimoPayload;
use App\Services\Botisimo\ClientDirectory;
use App\Services\Botisimo\CreditLedger;
use App\Services\Botisimo\Exceptions\BotisimoException;
use App\Services\Botisimo\InboxStore;
use App\Services\Channels\ChannelProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class AssistantInboxController extends Controller
{
    private const CONVERSATIONS_PER_PAGE = 20;

    private const MESSAGES_PER_PAGE = 30;

    public function __construct(private InboxStore $inbox) {}

    public function index(Request $request, Shop $shop)
    {
        return $this->render($request, $shop);
    }

    public function show(Request $request, Shop $shop, ShopBotConversation $conversation)
    {
        $this->ensureBelongsToShop($shop, $conversation);

        $this->inbox->refreshMessages($conversation);
        $conversation->update(['unread_count' => 0]);

        return $this->render($request, $shop, $conversation->fresh());
    }

    private function render(Request $request, Shop $shop, ?ShopBotConversation $conversation = null)
    {
        Gate::authorize('viewAssistant', $shop);

        $bot = $shop->bot;

        if (! $bot?->isConnected()) {
            return redirect()->route('backoffice.supplier.assistant.show', $shop);
        }

        return Inertia::render('Backoffice/Supplier/Assistant/Inbox', [
            'shop' => $shop->only('id', 'name'),
            'hasAccess' => $shop->hasAssistantAccess(),
            'conversations' => fn () => $bot->conversations()
                ->orderByDesc('last_message_at')
                ->paginate(self::CONVERSATIONS_PER_PAGE)
                ->through(fn ($item) => (new ShopBotConversationResource($item))->resolve($request)),
            'conversation' => fn () => $conversation
                ? (new ShopBotConversationResource($conversation))->resolve($request)
                : null,
            'templates' => fn () => $conversation && ! $conversation->canSendFreeMessage()
                ? $bot->templates()->where('status', ShopBotTemplate::STATUS_APPROVED)->latest('id')->get()
                    ->map(fn ($item) => (new ShopBotTemplateResource($item))->resolve($request))
                : [],
            'messages' => fn () => $conversation?->messages()
                ->orderByDesc('sent_at')->orderByDesc('id')
                ->paginate(self::MESSAGES_PER_PAGE, ['*'], 'messages_page')
                ->through(fn ($item) => (new ShopBotMessageResource($item))->resolve($request)),
        ]);
    }

    public function toggleAi(ToggleConversationAiRequest $request, Shop $shop, ShopBotConversation $conversation, BotisimoClient $client)
    {
        $this->ensureBelongsToShop($shop, $conversation);

        $enabled = $request->boolean('ai_enabled');

        try {
            $client->setConversationAi($conversation->botisimo_conversation_id, $enabled);
        } catch (BotisimoException $e) {
            return back()->with('error', $e->userMessage());
        }

        $conversation->update(['ai_enabled' => $enabled]);

        return back();
    }

    public function reply(ReplyConversationRequest $request, Shop $shop, ShopBotConversation $conversation, ChannelProvider $channel, CreditLedger $credits)
    {
        $this->ensureBelongsToShop($shop, $conversation);

        if (! $conversation->canSendFreeMessage() || blank($conversation->client_phone)) {
            return back()->with('error', 'Le client a écrit il y a plus de 24 h : seul un modèle approuvé peut lui être envoyé.');
        }

        $text = $request->validated('message');

        try {
            $response = $credits->spend(
                $shop,
                'message',
                (int) config('botisimo.credits.message_cost'),
                "conversation:{$conversation->id}",
                fn () => $channel->sendText($shop->bot, $conversation->client_phone, $text),
            );
        } catch (BotisimoException $e) {
            return back()->with('error', $e->userMessage());
        }

        $conversation->messages()->create([
            'message_id' => BotisimoPayload::get($response, 'message_id', 'id'),
            'meta_message_id' => BotisimoPayload::get($response, 'meta_message_id'),
            'direction' => ShopBotMessage::DIRECTION_OUTBOUND,
            'body' => $text,
            'status' => 'sent',
            'sent_at' => now(),
        ]);
        $this->inbox->refreshSummary($conversation);

        return back();
    }

    /** Hors de la fenêtre de 24 h, seul un modèle approuvé peut joindre le client. */
    public function sendTemplate(SendConversationTemplateRequest $request, Shop $shop, ShopBotConversation $conversation, BotisimoClient $client, ClientDirectory $clients, CreditLedger $credits)
    {
        $this->ensureBelongsToShop($shop, $conversation);

        if (blank($conversation->client_phone)) {
            return back()->with('error', 'Ce client n\'a pas de numéro connu.');
        }

        $bot = $shop->bot;
        $template = ShopBotTemplate::findOrFail($request->validated('template_id'));

        try {
            if ($clients->isOptedOut($bot, $conversation->client_phone)) {
                return back()->with('error', 'Ce client s\'est désinscrit : aucun modèle ne peut lui être envoyé.');
            }

            $response = $credits->spend(
                $shop,
                'marketing',
                (int) config('botisimo.credits.marketing_cost'),
                "conversation:{$conversation->id}",
                fn () => $client->sendMarketing([
                    'bot_id' => $bot->botisimo_bot_id,
                    'to' => ltrim($conversation->client_phone, '+'),
                    'template_name' => $template->name,
                    'language' => $template->language,
                    'parameters' => array_values($request->validated('parameters', [])),
                ]),
            );
        } catch (BotisimoException $e) {
            return back()->with('error', $e->userMessage());
        }

        $conversation->messages()->create([
            'message_id' => BotisimoPayload::get($response, 'message_id', 'id'),
            'meta_message_id' => BotisimoPayload::get($response, 'meta_message_id'),
            'direction' => ShopBotMessage::DIRECTION_OUTBOUND,
            'type' => 'template',
            'body' => "[Modèle] {$template->body()}",
            'status' => 'sent',
            'sent_at' => now(),
        ]);
        $this->inbox->refreshSummary($conversation);

        return back();
    }

    /** Une URL mêlant sa propre boutique et la conversation d'un autre doit échouer. */
    private function ensureBelongsToShop(Shop $shop, ShopBotConversation $conversation): void
    {
        Gate::authorize('viewAssistant', $shop);
        abort_unless($shop->bot !== null && $conversation->shop_bot_id === $shop->bot->id, 404);
    }
}
