<?php

namespace App\Http\Middleware;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'google_oauth_configured' => filled(config('services.google.client_id'))
                    && filled(config('services.google.client_secret'))
                    && filled(config('services.google.redirect')),
                'must_set_phone' => $request->user() && $request->user()->google_id && !$request->user()->phone,
                'is_vendeur' => (bool) $request->user()?->isVendeur(),
                'is_prestataire' => (bool) $request->user()?->isPrestataire(),
                'favorite_product_ids' => fn () => $request->user()
                    ? $request->user()->favoriteProductIds()
                    : [],
                'favorite_service_ids' => fn () => $request->user()
                    ? $request->user()->favoriteServiceIds()
                    : [],
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'currency' => config('payments.currency', 'XAF'),
            // Menu catégories de l'en-tête boutique (desktop) ; mis en cache 10 min.
            'nav_categories' => function () {
                try {
                    return Cache::remember('nav_categories', 600, fn () => Category::query()
                        ->orderBy('name')
                        ->get(['id', 'name'])
                        ->toArray());
                } catch (\Throwable) {
                    return []; // menu secondaire : la page doit s'afficher quand même
                }
            },
            'features' => [
                // Visible dès qu'une clé est renseignée ; si le mot de passe API manque,
                // le checkout affiche une erreur explicite (cf. CheckoutController).
                'pay_online_enabled' => filled(config('services.cinetpay.api_key')),
            ],
        ];
    }
}
