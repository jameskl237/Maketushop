<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\StoreAssistantTemplateRequest;
use App\Http\Resources\ShopBotTemplateResource;
use App\Models\Shop;
use App\Models\ShopBotTemplate;
use App\Services\Botisimo\Exceptions\BotisimoException;
use App\Services\Botisimo\TemplateCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class AssistantTemplateController extends Controller
{
    public function __construct(private TemplateCatalog $templates) {}

    public function index(Request $request, Shop $shop)
    {
        Gate::authorize('viewAssistant', $shop);

        $bot = $shop->bot;

        if (! $bot?->isConnected()) {
            return redirect()->route('backoffice.supplier.assistant.show', $shop);
        }

        $this->templates->refreshStatuses($bot);

        return Inertia::render('Backoffice/Supplier/Assistant/Templates', [
            'shop' => $shop->only('id', 'name'),
            'hasAccess' => $shop->hasAssistantAccess(),
            'templates' => $bot->templates()->latest('id')->get()
                ->map(fn ($item) => (new ShopBotTemplateResource($item))->resolve($request)),
        ]);
    }

    public function store(StoreAssistantTemplateRequest $request, Shop $shop)
    {
        abort_unless($shop->bot?->isConnected(), 409);

        try {
            $this->templates->create($shop->bot, $request->validated());
        } catch (BotisimoException $e) {
            return back()->with('error', $e->userMessage());
        }

        return back()->with('success', 'Modèle envoyé à Meta pour validation.');
    }

    public function destroy(Shop $shop, ShopBotTemplate $template)
    {
        Gate::authorize('useAssistant', $shop);
        abort_unless($template->shop_bot_id === $shop->bot?->id, 404);

        try {
            $this->templates->delete($template);
        } catch (BotisimoException $e) {
            return back()->with('error', $e->userMessage());
        }

        return back()->with('success', 'Modèle supprimé.');
    }
}
