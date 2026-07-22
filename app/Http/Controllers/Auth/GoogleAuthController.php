<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (! $this->hasGoogleConfiguration()) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'google' => 'Configuration Google OAuth manquante (client_id, client_secret, redirect).',
                ]);
        }

        // Preserve an optional redirect query param so we can return the user there after OAuth
        $redirect = request()->query('redirect');
        if ($redirect) {
            request()->session()->put('auth_google_redirect', $redirect);
        }

        // Preserve the account type chosen on the registration form (client/vendeur/prestataire/both),
        // so a brand-new account created via Google gets the role the user actually asked for.
        $accountType = request()->query('account_type');
        if ($accountType) {
            request()->session()->put('auth_google_account_type', $accountType);
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        if (! $this->hasGoogleConfiguration()) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'google' => 'Configuration Google OAuth manquante (client_id, client_secret, redirect).',
                ]);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $exception) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'google' => 'Connexion Google impossible. Merci de reessayer.',
                ]);
        }

        $email = (string) ($googleUser->getEmail() ?? '');

        if ($email === '') {
            return redirect()
                ->route('login')
                ->withErrors([
                    'google' => 'Aucun email n\'a ete fourni par Google.',
                ]);
        }

        $user = User::query()
            ->where('google_id', $googleUser->getId())
            ->orWhere('email', $email)
            ->first();

        // Compte choisi sur le formulaire d'inscription (client/vendeur/prestataire/both),
        // absent si l'utilisateur est arrivé par le bouton Google générique (page de connexion).
        $accountType = $request->session()->pull('auth_google_account_type');

        if ($user) {
            // Un compte existant conserve son rôle et ses capacités : on ne fait que
            // rattacher google_id / confirmer l'email, sans jamais écraser le rôle.
            $user->forceFill([
                'google_id' => $user->google_id ?: $googleUser->getId(),
                'email_verified_at' => $user->email_verified_at ?: now(),
            ])->save();
        } else {
            $baseUsername = Str::slug($googleUser->getName() ?: Str::before($email, '@'), '_');
            $username = $this->buildUniqueUsername($baseUsername !== '' ? $baseUsername : 'user');

            $user = User::create([
                'name' => $googleUser->getName() ?: $username,
                'username' => $username,
                'email' => $email,
                'google_id' => $googleUser->getId(),
                'password' => Hash::make(Str::random(32)),
                'email_verified_at' => now(),
                ...$this->resolveNewAccountAttributes($accountType),
            ]);
        }

    Auth::login($user, true);
    $request->session()->regenerate();

    // If we stored a redirect before starting OAuth, use it
    $storedRedirect = $request->session()->pull('auth_google_redirect');
    if ($storedRedirect) {
        return redirect($storedRedirect);
    }

    // Default: redirect to the dashboard adapted to the role
    return redirect()->route($user->dashboardRouteName());
    }

    /**
     * Détermine role/is_vendeur/is_prestataire pour un compte créé via Google.
     *
     * - 'client' | 'vendeur' | 'prestataire' | 'both' : rôle explicitement choisi
     *   sur le formulaire d'inscription (même mapping que RegisteredUserController).
     * - Aucun account_type (bouton Google générique, ex. page de connexion) : compte
     *   mixte pouvant vendre, proposer des services, et commander comme un client.
     */
    private function resolveNewAccountAttributes(?string $accountType): array
    {
        if (! $accountType) {
            return [
                'role' => User::ROLE_SUPPLIER,
                'is_vendeur' => true,
                'is_prestataire' => true,
            ];
        }

        $isVendeur = in_array($accountType, ['vendeur', 'both', 'supplier'], true);
        $isPrestataire = in_array($accountType, ['prestataire', 'both', 'supplier'], true);
        $isPro = $isVendeur || $isPrestataire;

        return [
            'role' => $isPro ? User::ROLE_SUPPLIER : User::ROLE_USER,
            'is_vendeur' => $isVendeur,
            'is_prestataire' => $isPrestataire,
        ];
    }

    private function buildUniqueUsername(string $base): string
    {
        $username = $base;
        $counter = 1;

        while (User::query()->where('username', $username)->exists()) {
            $username = "{$base}_{$counter}";
            $counter++;
        }

        return $username;
    }

    private function hasGoogleConfiguration(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
    }
}


