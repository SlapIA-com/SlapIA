<?php

namespace App\Http\Controllers;

use App\Models\AvisClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Espace client — port de pages/dashboard.php + api/dashboard-*.php.
 * Chaque action cible exclusivement le client de la session courante
 * (jamais un ID fourni par le client), même discipline que l'ancien site.
 */
class DashboardController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $client = $request->user()->client;

        if ($client->isAdmin()) {
            return redirect()->route('admin.index');
        }

        $client->load(['prestations', 'factures', 'latestAvis']);

        return Inertia::render('Dashboard/Index', [
            'client' => [
                'nom_complet' => $client->nom_complet,
                'email' => $request->user()->email,
                'nom_entreprise' => $client->nom_entreprise,
                'telephone' => $client->telephone,
                'location' => $client->location,
                'linkedin' => $client->linkedin,
                'photo_url' => $client->photo_path ? route('avatar', $client->id) : null,
                'derniere_connexion' => $request->user()->derniere_connexion,
                'commandes_libres' => $client->commandes_libres,
            ],
            'prestation' => $client->prestations->last(),
            'factures' => $client->factures->map(fn ($f) => [
                'id' => $f->id,
                'nom_fichier' => $f->nom_fichier,
            ]),
            'avis' => $client->latestAvis,
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $compte = $request->user();

        if (!Hash::check($data['current_password'], $compte->mot_de_passe_hash ?? '')) {
            throw ValidationException::withMessages([
                'current_password' => trans('messages.dashboard.err_wrong_current_password'),
            ]);
        }

        $compte->forceFill(['mot_de_passe_hash' => Hash::make($data['password'])])->save();

        return back()->with('success', trans('messages.dashboard.password_updated'));
    }

    public function updateLinkedin(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'linkedin' => ['nullable', 'string', 'regex:/^https?:\/\//'],
        ]);

        $request->user()->client->update(['linkedin' => $data['linkedin'] ?: null]);

        return back()->with('success', trans('messages.dashboard.linkedin_updated'));
    }

    public function updateContact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'telephone' => ['nullable', 'string', 'max:30'],
            'location' => ['nullable', 'string', 'max:500'],
        ]);

        $request->user()->client->update($data);

        return back()->with('success', trans('messages.dashboard.phone_updated'));
    }

    public function updateReview(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'commentaire' => ['required', 'string', 'max:2000'],
            'satisfaction' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $client = $request->user()->client;

        AvisClient::updateOrCreate(
            ['client_id' => $client->id],
            [
                'prenom_nom' => $client->nom_complet,
                'commentaire' => $data['commentaire'],
                'satisfaction' => $data['satisfaction'],
                'created_at' => now(),
            ]
        );

        return back()->with('success', trans('messages.dashboard.avis_updated'));
    }

    public function uploadPhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
        ]);

        $client = $request->user()->client;
        $path = $request->file('photo')->store('avatars', 'local');

        if ($client->photo_path) {
            Storage::disk('local')->delete($client->photo_path);
        }

        $client->update([
            'photo_path' => $path,
            'photo_mime' => $request->file('photo')->getMimeType(),
        ]);

        return back()->with('success', trans('messages.dashboard.photo_updated'));
    }

    public function viewInvoice(Request $request, int $index)
    {
        $client = $request->user()->client;
        $facture = $client->factures()->orderBy('id')->get()->get($index);

        abort_if(!$facture, 404);

        return Storage::disk('local')->response(
            $facture->chemin_fichier,
            $facture->nom_fichier,
            ['Content-Type' => $facture->mime_type, 'Content-Disposition' => 'inline']
        );
    }
}
