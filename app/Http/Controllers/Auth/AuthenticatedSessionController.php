<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Connexion / déconnexion. Reprend les règles de l'ancien api/auth-login.php
 * (includes/auth.php) : rate limit par email ET par IP, message générique en
 * cas d'échec, mise à jour de "derniere_connexion", redirection par rôle.
 */
class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

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
