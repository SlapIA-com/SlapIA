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

        ContactMessage::create([
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

        return redirect()->route('contact', ['sent' => 1]);
    }
}
