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

        if ($user) {
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
                'role' => User::ROLE_USER,
                'password' => Hash::make(Str::random(32)),
                'email_verified_at' => now(),
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
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


