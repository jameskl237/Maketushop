<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\StoreAssistantCampaignRequest;
use App\Http\Resources\ShopBotCampaignResource;
use App\Http\Resources\ShopBotTemplateResource;
use App\Jobs\Botisimo\SendCampaign;
use App\Models\Shop;
use App\Models\ShopBotCampaign;
use App\Models\ShopBotTemplate;
use App\Services\Botisimo\CampaignSender;
use App\Services\Botisimo\CreditLedger;
use App\Services\Botisimo\Exceptions\BotisimoException;
use App\Services\Botisimo\TemplateCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class AssistantCampaignController extends Controller
{
    public function __construct(private CampaignSender $sender) {}

    public function index(Request $request, Shop $shop, CreditLedger $credits, TemplateCatalog $templates)
    {
        Gate::authorize('viewAssistant', $shop);

        $bot = $shop->bot;

        if (! $bot?->isConnected()) {
            return redirect()->route('backoffice.supplier.assistant.show', $shop);
        }

        $templates->refreshStatuses($bot);
        $this->sender->refreshHistory($bot);

        return Inertia::render('Backoffice/Supplier/Assistant/Campaigns', [
            'shop' => $shop->only('id', 'name'),
            'hasAccess' => $shop->hasAssistantAccess(),
            'templates' => $bot->templates()->where('status', ShopBotTemplate::STATUS_APPROVED)->latest('id')->get()
                ->map(fn ($item) => (new ShopBotTemplateResource($item))->resolve($request)),
            'campaigns' => $bot->campaigns()->latest('id')->paginate(15)
                ->through(fn ($item) => (new ShopBotCampaignResource($item))->resolve($request)),
            'credits' => ['quota' => $credits->quota($shop), 'remaining' => $credits->remaining($shop)],
        ]);
    }

    public function estimate(Request $request, Shop $shop)
    {
        Gate::authorize('useAssistant', $shop);
        abort_unless($shop->bot?->isConnected(), 409);

        $data = $request->validate(['tags' => ['nullable', 'array', 'max:10'], 'tags.*' => ['string', 'max:50']]);

        try {
            return response()->json($this->sender->estimate($shop->bot, $data['tags'] ?? []));
        } catch (BotisimoException $e) {
            return response()->json(['message' => $e->userMessage()], 503);
        }
    }

    public function store(StoreAssistantCampaignRequest $request, Shop $shop)
    {
        abort_unless($shop->bot?->isConnected(), 409);

        $data = $request->validated();
        $template = ShopBotTemplate::findOrFail($data['template_id']);
        $scheduledAt = isset($data['scheduled_at']) ? Carbon::parse($data['scheduled_at']) : null;

        $campaign = $shop->bot->campaigns()->create([
            'channel' => $shop->bot->channel,
            'template_name' => $template->name,
            'status' => ShopBotCampaign::STATUS_SCHEDULED,
            'scheduled_at' => $scheduledAt,
            'payload' => [
                'template_id' => $template->id,
                'language' => $template->language,
                'tags' => $data['tags'] ?? [],
                'parameters' => array_values($data['parameters'] ?? []),
            ],
        ]);

        if ($scheduledAt === null) {
            SendCampaign::dispatch($campaign->id);
        }

        return back()->with('success', $scheduledAt ? 'Campagne planifiée.' : 'Campagne lancée.');
    }

    public function cancel(Shop $shop, ShopBotCampaign $campaign)
    {
        Gate::authorize('useAssistant', $shop);
        abort_unless($campaign->shop_bot_id === $shop->bot?->id, 404);

        $cancelled = ShopBotCampaign::whereKey($campaign->id)
            ->where('status', ShopBotCampaign::STATUS_SCHEDULED)
            ->update(['status' => ShopBotCampaign::STATUS_CANCELLED]);

        return $cancelled === 1
            ? back()->with('success', 'Campagne annulée.')
            : back()->with('error', 'Cette campagne est déjà partie.');
    }
}
