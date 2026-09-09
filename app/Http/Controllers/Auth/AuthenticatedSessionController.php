<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Connexion / déconnexion. Reprend les règles de l'ancien api/auth-login.php
 * (includes/auth.php) : rate limit par email ET par IP, message générique en
 * cas d'échec, mise à jour de "derniere_connexion", redirection par rôle.
 * Turnstile ajouté le 6 septembre 2026 (même protection que contact et
 * mot de passe oublié) pour limiter le bruteforce en amont du rate limit.
 */
class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'turnstileSiteKey' => config('services.turnstile.site_key'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'cf-turnstile-response' => ['nullable', 'string'],
        ]);

        $secret = config('services.turnstile.secret_key');
        if ($secret) {
            $verify = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secret,
                'response' => $request->input('cf-turnstile-response', ''),
                'remoteip' => $request->ip(),
            ])->json();

            if (empty($verify['success'])) {
                throw ValidationException::withMessages([
                    'email' => 'Validation de sécurité échouée.',
                ]);
            }
        }

        $throttleKey = strtolower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => trans('messages.auth.err_rate_limit'),
            ]);
        }

        if (!Auth::attempt(
            ['email' => $credentials['email'], 'password' => $credentials['password']],
            $request->boolean('remember')
        )) {
            RateLimiter::hit($throttleKey, 900);
            throw ValidationException::withMessages([
                'email' => trans('messages.auth.err_invalid'),
            ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        $compte = Auth::user();
        $compte->forceFill(['derniere_connexion' => now()])->save();

        /** @var Client|null $client */
        $client = $compte->client;

        return redirect()->intended(
            $client && $client->isAdmin() ? route('admin.index') : route('dashboard')
        );
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
