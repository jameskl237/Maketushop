<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\ActivateAssistantRequest;
use App\Http\Requests\Vendor\UpdateCatalogItemRequest;
use App\Http\Resources\ShopBotProductResource;
use App\Http\Resources\ShopBotResource;
use App\Jobs\Botisimo\ReconcileShopCatalog;
use App\Jobs\Botisimo\SyncProductToBotisimo;
use App\Models\Shop;
use App\Models\ShopBot;
use App\Models\ShopBotProduct;
use App\Services\Botisimo\AssistantProvisioner;
use App\Services\Botisimo\BotisimoClient;
use App\Services\Botisimo\Exceptions\BotisimoException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AssistantController extends Controller
{
    /** Paramètres ajoutés par Botisimo au callback : hors du périmètre de la signature. */
    private const CALLBACK_PARAMS = ['success', 'error', 'bot_id', 'phone_number_id', 'phone_number'];

    public function __construct(private AssistantProvisioner $provisioner) {}

    public function index(Request $request)
    {
        $shop = $request->user()->shops()->oldest()->first();

        return $shop
            ? redirect()->route('backoffice.supplier.assistant.show', $shop)
            : redirect()->route('backoffice.supplier.shops.index')->with('error', 'Créez d\'abord une boutique pour activer l\'assistant IA.');
    }

    public function show(Request $request, Shop $shop)
    {
        Gate::authorize('viewAssistant', $shop);

        $bot = $shop->bot;

        return Inertia::render('Backoffice/Supplier/Assistant/Show', [
            'shop' => $shop->only('id', 'name'),
            'shops' => $request->user()->shops()->oldest()->get(['id', 'name']),
            'hasAccess' => $shop->hasAssistantAccess(),
            'bot' => $bot ? (new ShopBotResource($bot))->resolve($request) : null,
            'catalogStats' => fn () => $bot ? $this->catalogStats($shop, $bot) : null,
            'catalog' => fn () => $bot?->catalogItems()->with('catalogable')->latest('id')->paginate(10)
                ->through(fn (ShopBotProduct $item) => (new ShopBotProductResource($item))->resolve($request)),
        ]);
    }

    private function catalogStats(Shop $shop, ShopBot $bot): array
    {
        $items = $bot->catalogItems();

        return [
            'total' => $shop->products()->count() + $shop->services()->count(),
            'synced' => (clone $items)->whereNotNull('botisimo_product_id')->whereNull('sync_error')->count(),
            'errors' => (clone $items)->whereNotNull('sync_error')->count(),
            'last_synced_at' => $bot->last_synced_at?->toIso8601String(),
        ];
    }

    public function activate(ActivateAssistantRequest $request, Shop $shop)
    {
        try {
            $this->provisioner->provision($shop, $request->validated());
        } catch (BotisimoException $e) {
            return back()->with('error', $e->userMessage());
        }

        return back()->with('success', 'Votre assistant est créé. Connectez maintenant votre WhatsApp Business.');
    }

    public function connect(Shop $shop)
    {
        Gate::authorize('useAssistant', $shop);

        $bot = $shop->bot;
        abort_unless($bot?->isProvisioned(), 409);

        $callbackUrl = URL::temporarySignedRoute(
            'backoffice.supplier.assistant.callback',
            now()->addMinutes((int) config('botisimo.callback_ttl_minutes')),
            ['shopBot' => $bot->id],
        );

        try {
            return Inertia::location($this->provisioner->startConnection($bot, $callbackUrl));
        } catch (BotisimoException $e) {
            return back()->with('error', $e->userMessage());
        }
    }

    public function callback(Request $request, ShopBot $shopBot)
    {
        abort_unless($request->hasValidSignatureWhileIgnoring(self::CALLBACK_PARAMS), 403);
        Gate::authorize('manage', $shopBot);
        abort_unless((string) $request->query('bot_id') === (string) $shopBot->botisimo_bot_id, 403);

        $redirect = redirect()->route('backoffice.supplier.assistant.show', $shopBot->shop_id);

        if ($request->query('success') !== 'true') {
            $reason = Str::limit(strip_tags((string) $request->query('error', 'Connexion annulée.')), 200);
            $this->provisioner->failConnection($shopBot, "La connexion WhatsApp a échoué : {$reason}");

            return $redirect->with('error', 'La connexion WhatsApp a échoué. Vous pouvez la relancer.');
        }

        try {
            $this->provisioner->completeConnection(
                $shopBot,
                $request->query('phone_number'),
                $request->query('phone_number_id'),
            );
        } catch (BotisimoException $e) {
            return $redirect->with('error', $e->userMessage());
        }

        return $redirect->with('success', 'Votre WhatsApp Business est connecté et votre assistant est actif.');
    }

    /** Reprise si le numéro est connecté mais que l'activation a échoué au retour de Meta. */
    public function finalize(Shop $shop)
    {
        Gate::authorize('useAssistant', $shop);

        $bot = $shop->bot;
        abort_unless($bot?->isConnected(), 409);

        try {
            $this->provisioner->completeConnection($bot);
        } catch (BotisimoException $e) {
            return back()->with('error', $e->userMessage());
        }

        return back()->with('success', 'Votre assistant est actif.');
    }

    public function syncCatalog(Shop $shop, BotisimoClient $client)
    {
        Gate::authorize('useAssistant', $shop);

        $bot = $shop->bot;
        abort_unless($bot?->isProvisioned(), 409);

        try {
            $client->setCatalogAi($bot->botisimo_bot_id, true);
        } catch (BotisimoException $e) {
            return back()->with('error', $e->userMessage());
        }

        $bot->update(['catalog_ai_enabled' => true]);
        ReconcileShopCatalog::dispatch($bot->id);

        return back()->with('success', 'Synchronisation du catalogue lancée.');
    }

    public function updateCatalogItem(UpdateCatalogItemRequest $request, Shop $shop, ShopBotProduct $catalogItem)
    {
        abort_unless($catalogItem->shop_bot_id === $shop->bot?->id, 404);

        $catalogItem->update($request->validated());
        SyncProductToBotisimo::dispatch($catalogItem->shop_bot_id, $catalogItem->catalogable_type, $catalogItem->catalogable_id);

        return back();
    }
}
