<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\UpdateAssistantSettingsRequest;
use App\Http\Resources\ShopBotSettingsResource;
use App\Models\Shop;
use App\Services\Botisimo\AssistantProvisioner;
use App\Services\Botisimo\AssistantStats;
use App\Services\Botisimo\Exceptions\BotisimoException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AssistantSettingsController extends Controller
{
    public function __construct(private AssistantProvisioner $provisioner) {}

    public function edit(Request $request, Shop $shop)
    {
        Gate::authorize('viewAssistant', $shop);

        if (! $shop->bot?->isProvisioned()) {
            return redirect()->route('backoffice.supplier.assistant.show', $shop);
        }

        return Inertia::render('Backoffice/Supplier/Assistant/Settings', [
            'shop' => $shop->only('id', 'name'),
            'hasAccess' => $shop->hasAssistantAccess(),
            'settings' => (new ShopBotSettingsResource($shop->bot))->resolve($request),
        ]);
    }

    public function update(UpdateAssistantSettingsRequest $request, Shop $shop)
    {
        abort_unless($shop->bot?->isProvisioned(), 409);

        try {
            $this->provisioner->updateSettings($shop->bot, $request->validated());
        } catch (BotisimoException $e) {
            return back()->with('error', $e->userMessage());
        }

        return back()->with('success', 'Paramètres enregistrés.');
    }

    /** Désactiver reste possible sans abonnement : c'est ce qui arrête la dépense. */
    public function setStatus(Request $request, Shop $shop)
    {
        $data = $request->validate(['active' => ['required', 'boolean']]);
        $active = (bool) $data['active'];

        Gate::authorize($active ? 'useAssistant' : 'viewAssistant', $shop);

        $bot = $shop->bot;
        abort_unless($bot?->isConnected(), 409);

        try {
            $this->provisioner->setActive($bot, $active);
        } catch (BotisimoException $e) {
            return back()->with('error', $e->userMessage());
        }

        return back()->with('success', $active ? 'Assistant activé.' : 'Assistant désactivé.');
    }

    public function destroy(Request $request, Shop $shop)
    {
        Gate::authorize('viewAssistant', $shop);

        $bot = $shop->bot;
        abort_unless($bot !== null, 404);

        $request->validate(['confirmation' => ['required', 'string', Rule::in([$shop->name])]], [
            'confirmation.in' => 'Saisissez exactement le nom de la boutique.',
        ]);

        try {
            $this->provisioner->destroy($bot);
        } catch (BotisimoException $e) {
            return back()->with('error', $e->userMessage());
        }

        return redirect()->route('backoffice.supplier.assistant.show', $shop)
            ->with('success', 'Assistant supprimé.');
    }

    public function stats(Shop $shop, AssistantStats $stats)
    {
        Gate::authorize('viewAssistant', $shop);

        if (! $shop->bot?->isProvisioned()) {
            return redirect()->route('backoffice.supplier.assistant.show', $shop);
        }

        return Inertia::render('Backoffice/Supplier/Assistant/Stats', [
            'shop' => $shop->only('id', 'name'),
            'stats' => $stats->for($shop->bot),
        ]);
    }
}
