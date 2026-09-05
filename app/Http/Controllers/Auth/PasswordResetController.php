<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Compte;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Mot de passe oublié. Reprend api/auth-reset-request.php / auth-reset-exec.php :
 * - toujours répondre "succès" côté request, qu'un compte existe ou non
 *   (jamais révéler l'existence d'un email) ;
 * - token 32 bytes hex, expiration 1h ;
 * - notification envoyée via le webhook n8n existant (N8N_AUTH_WEBHOOK_URL),
 *   pas un mailer Laravel classique, pour rester sur l'infra email déjà en place.
 */
class PasswordResetController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $compte = Compte::where('email', $request->email)->first();

        if ($compte) {
            $token = Str::random(64);
            $compte->forceFill([
                'reset_token' => $token,
                'reset_token_expiry' => now()->addHour(),
            ])->save();

            $webhook = config('services.n8n.auth_webhook_url');
            if ($webhook) {
                try {
                    Http::timeout(10)->post($webhook, [
                        'event' => 'password_reset',
                        'email' => $compte->email,
                        'name' => $compte->client?->nom_complet ?? '',
                        'reset_url' => route('password.edit', [
                            'token' => $token,
                            'email' => $compte->email,
                        ]),
                    ]);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        return back()->with('success', trans('messages.auth.reset_request_sent'));
    }

    public function edit(Request $request): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $request->route('token'),
            'email' => $request->query('email', ''),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $compte = Compte::where('email', $data['email'])->first();

        $valid = $compte
            && $compte->reset_token
            && hash_equals($compte->reset_token, $data['token'])
            && $compte->reset_token_expiry
            && $compte->reset_token_expiry->isFuture();

        if (!$valid) {
            throw ValidationException::withMessages([
                'email' => trans('messages.auth.err_reset_invalid'),
            ]);
        }

        $compte->forceFill([
            'mot_de_passe_hash' => Hash::make($data['password']),
            'reset_token' => null,
            'reset_token_expiry' => null,
        ])->save();

        return redirect()->route('login')->with('success', trans('messages.dashboard.password_updated'));
    }
}
