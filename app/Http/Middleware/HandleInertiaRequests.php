<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
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
            // Paiement en ligne : actif uniquement si explicitement activé ET clés NotchPay présentes.
            // Tant que false, les boutons "payer sur la plateforme" sont grisés (Bientôt disponible).
            'features' => [
                'pay_online_enabled' => filled(config('services.notchpay.public_key'))
                    && filled(config('services.notchpay.secret_key'))
                    && env('PAY_ONLINE_ENABLED', false),
            ],
        ];
    }
}
