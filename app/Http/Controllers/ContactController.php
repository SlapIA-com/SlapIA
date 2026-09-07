<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\Client;
use App\Models\Compte;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** Port de pages/contact.php + includes/contact-form.php. */
class ContactController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('Contact', [
            'sent' => $request->boolean('sent'),
            'subjects' => ContactMessage::SUBJECTS,
            'turnstileSiteKey' => config('services.turnstile.site_key'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $key = 'contact:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'message' => trans('messages.contact.err_rate_limit'),
            ]);
        }
        RateLimiter::hit($key, 900);

        $data = $request->validate([
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'company' => ['nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'in:'.implode(',', array_keys(ContactMessage::SUBJECTS))],
            'message' => ['required', 'string', 'max:5000'],
            'consent' => ['accepted'],
            'cf-turnstile-response' => ['nullable', 'string'],
        ]);

        $secret = config('services.turnstile.secret_key');
        if ($secret) {
            $verify = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secret,
                'response' => $data['cf-turnstile-response'] ?? '',
                'remoteip' => $request->ip(),
            ])->json();

            if (empty($verify['success'])) {
                throw ValidationException::withMessages([
                    'message' => 'Validation de sécurité échouée.',
                ]);
            }
        }

        $client = Client::whereHas('compte', fn ($q) => $q->where('email', $data['email']))->first();

        $message = ContactMessage::create([
            'client_id' => $client?->id,
            'prenom' => $data['firstname'],
            'nom' => $data['lastname'] ?: null,
            'nom_entreprise' => $data['company'] ?: null,
            'email' => $data['email'],
            'sujet' => ContactMessage::SUBJECTS[$data['subject']],
            'message' => $data['message'],
            'prise_de_contact_ok' => false,
            'date_creation' => now(),
        ]);

        // Remplace le déclencheur Notion "NEW Contact web" : un seul appel,
        // le workflow n8n envoie en parallèle l'email pour Thomas et l'email
        // de confirmation au client (voir config('services.n8n')).
        $webhook = config('services.n8n.contact_webhook_url');
        if ($webhook) {
            try {
                Http::timeout(10)->post($webhook, [
                    'event' => 'new_contact',
                    'prenom' => $message->prenom,
                    'nom' => $message->nom ?? '',
                    'nom_entreprise' => $message->nom_entreprise ?? '',
                    'email' => $message->email,
                    'sujet' => $message->sujet,
                    'message' => $message->message,
                    'date' => $message->date_creation->toIso8601String(),
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('contact', ['sent' => 1]);
    }
}
