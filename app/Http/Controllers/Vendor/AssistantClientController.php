<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\StoreAssistantClientRequest;
use App\Http\Requests\Vendor\UpdateAssistantClientRequest;
use App\Models\Shop;
use App\Services\Botisimo\ClientDirectory;
use App\Services\Botisimo\Exceptions\BotisimoConflictException;
use App\Services\Botisimo\Exceptions\BotisimoException;
use App\Services\Botisimo\Exceptions\BotisimoNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class AssistantClientController extends Controller
{
    public function __construct(private ClientDirectory $clients) {}

    public function index(Request $request, Shop $shop)
    {
        Gate::authorize('viewAssistant', $shop);

        $bot = $shop->bot;

        if (! $bot?->isConnected()) {
            return redirect()->route('backoffice.supplier.assistant.show', $shop);
        }

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'tag' => ['nullable', 'string', 'max:50'],
            'opted_out' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $page = (int) ($filters['page'] ?? 1);
        $result = ['clients' => [], 'has_more' => false];
        $error = null;

        try {
            $result = $this->clients->list($bot, $filters, $page);
        } catch (BotisimoException $e) {
            $error = $e->userMessage();
        }

        return Inertia::render('Backoffice/Supplier/Assistant/Clients', [
            'shop' => $shop->only('id', 'name'),
            'hasAccess' => $shop->hasAssistantAccess(),
            'clients' => $result['clients'],
            'hasMore' => $result['has_more'],
            'page' => $page,
            'filters' => $filters,
            'loadError' => $error,
        ]);
    }

    public function store(StoreAssistantClientRequest $request, Shop $shop)
    {
        abort_unless($shop->bot?->isConnected(), 409);

        try {
            $this->clients->create($shop->bot, $request->validated());
        } catch (BotisimoConflictException) {
            return back()->withErrors(['phone' => 'Ce numéro est déjà enregistré.']);
        } catch (BotisimoException $e) {
            return back()->with('error', $e->userMessage());
        }

        return back()->with('success', 'Client ajouté.');
    }

    public function update(UpdateAssistantClientRequest $request, Shop $shop, string $client)
    {
        abort_unless($shop->bot?->isConnected(), 409);

        try {
            $this->clients->update($shop->bot, $client, $request->validated());
        } catch (BotisimoNotFoundException) {
            abort(404);
        } catch (BotisimoException $e) {
            return back()->with('error', $e->userMessage());
        }

        return back()->with('success', 'Client mis à jour.');
    }

    public function destroy(Shop $shop, string $client)
    {
        Gate::authorize('useAssistant', $shop);
        abort_unless($shop->bot?->isConnected(), 409);

        try {
            $this->clients->delete($shop->bot, $client);
        } catch (BotisimoNotFoundException) {
            abort(404);
        } catch (BotisimoException $e) {
            return back()->with('error', $e->userMessage());
        }

        return back()->with('success', 'Client supprimé.');
    }
}
