<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AvisClient;
use App\Models\Client;
use App\Models\Compte;
use App\Models\Prestation;
use App\Models\RssSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dashboard admin — port de pages/admin.php + tous les api/admin-*.php.
 * Une seule page React à onglets (Vue d'ensemble / Comptes / Abonnés RSS /
 * Factures / Avis), même découpage que l'ancien site.
 */
class AdminController extends Controller
{
    public function index(): Response
    {
        $clients = Client::with(['compte', 'prestations', 'factures'])
            ->orderBy('nom_complet')
            ->get()
            ->map(fn (Client $c) => $this->accountRow($c));

        $rss = RssSubscriber::orderByDesc('date_creation')->get(['email', 'date_creation']);

        $reviews = AvisClient::with('client')->orderByDesc('created_at')->get()->map(fn (AvisClient $a) => [
            'id' => $a->id,
            'client_id' => $a->client_id,
            'prenom_nom' => $a->prenom_nom,
            'satisfaction' => $a->satisfaction,
            'commentaire' => $a->commentaire,
            'created_at' => $a->created_at,
        ]);

        $billingBreakdown = Prestation::selectRaw('statut_facturation, count(*) as total')
            ->groupBy('statut_facturation')->pluck('total', 'statut_facturation');

        $roleBreakdown = [
            'particulier' => Client::where('type_client', 'Particulier')->count(),
            'entreprise' => Client::where('type_client', 'Entreprise')->count(),
            'admin' => Client::whereNull('type_client')->count(),
        ];

        return Inertia::render('Admin/Index', [
            'kpis' => [
                'comptes' => $clients->count(),
                'abonnes_rss' => $rss->count(),
                'factures_en_attente' => Prestation::where('statut_facturation', 'En attente')->count(),
            ],
            'billingBreakdown' => $billingBreakdown,
            'roleBreakdown' => $roleBreakdown,
            'accounts' => $clients,
            'rssSubscribers' => $rss,
            'reviews' => $reviews,
        ]);
    }

    private function accountRow(Client $c): array
    {
        return [
            'id' => $c->id,
            'nom_complet' => $c->nom_complet,
            'email' => $c->compte->email,
            'nom_entreprise' => $c->nom_entreprise,
            'telephone' => $c->telephone,
            'location' => $c->location,
            'role' => $c->role(),
            'derniere_connexion' => $c->compte->derniere_connexion,
            'prestations' => $c->prestations->map(fn (Prestation $p) => [
                'id' => $p->id,
                'type_service' => $p->type_service,
                'prix' => $p->prix,
                'statut_facturation' => $p->statut_facturation,
                'description' => $p->description,
                'date_debut' => $p->date_debut,
                'date_fin' => $p->date_fin,
            ]),
            'factures' => $c->factures->map(fn ($f) => ['id' => $f->id, 'nom_fichier' => $f->nom_fichier]),
            'commandes_libres' => $c->commandes_libres,
        ];
    }

    public function updateAccount(Request $request, Client $client): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', 'in:particulier,entreprise,admin'],
        ]);

        $client->update([
            'type_client' => match ($data['role']) {
                'entreprise' => 'Entreprise',
                'particulier' => 'Particulier',
                default => null,
            },
        ]);

        return back()->with('success', 'Compte mis à jour.');
    }

    public function updateProfile(Request $request, Client $client): RedirectResponse
    {
        $data = $request->validate([
            'telephone' => ['nullable', 'string', 'max:30'],
            'location' => ['nullable', 'string', 'max:500'],
            'commandes_libres' => ['nullable', 'string'],
        ]);

        $client->update($data);

        return back()->with('success', 'Fiche mise à jour.');
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $compte = Compte::where('email', $data['email'])->first();
        abort_if(!$compte, 404, 'Aucun compte trouvé avec cet email.');

        $generated = Str::password(12);
        $compte->forceFill(['mot_de_passe_hash' => Hash::make($generated)])->save();

        return back()->with('success', "Mot de passe généré : {$generated}");
    }

    public function createClient(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nom_complet' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:comptes,email'],
            'nom_entreprise' => ['nullable', 'string', 'max:255'],
            'job_domaine' => ['nullable', 'string', 'max:255'],
            'linkedin' => ['nullable', 'string'],
            'role' => ['required', 'in:particulier,entreprise,admin'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $plainPassword = $data['password'] ?: Str::password(12);

        $compte = Compte::create([
            'email' => $data['email'],
            'mot_de_passe_hash' => Hash::make($plainPassword),
            'mail_avis' => true,
        ]);

        Client::create([
            'compte_id' => $compte->id,
            'nom_complet' => $data['nom_complet'],
            'nom_entreprise' => $data['nom_entreprise'] ?? null,
            'job_domaine' => $data['job_domaine'] ?? null,
            'linkedin' => $data['linkedin'] ?? null,
            'type_client' => match ($data['role']) {
                'entreprise' => 'Entreprise',
                'particulier' => 'Particulier',
                default => null,
            },
        ]);

        return back()->with('success', "Client créé. Mot de passe généré : {$plainPassword}");
    }

    public function storePrestation(Request $request, Client $client): RedirectResponse
    {
        $data = $request->validate([
            'type_service' => ['nullable', 'string', 'max:255'],
            'prix' => ['nullable', 'numeric'],
            'statut_facturation' => ['required', 'in:'.implode(',', Prestation::STATUTS)],
            'description' => ['nullable', 'string'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date'],
        ]);

        $client->prestations()->create($data);

        return back()->with('success', 'Prestation enregistrée.');
    }

    public function updatePrestation(Request $request, Prestation $prestation): RedirectResponse
    {
        $data = $request->validate([
            'type_service' => ['nullable', 'string', 'max:255'],
            'prix' => ['nullable', 'numeric'],
            'statut_facturation' => ['required', 'in:'.implode(',', Prestation::STATUTS)],
            'description' => ['nullable', 'string'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date'],
        ]);

        $prestation->update($data);

        return back()->with('success', 'Prestation enregistrée.');
    }

    public function destroyPrestation(Prestation $prestation): RedirectResponse
    {
        $prestation->delete();

        return back()->with('success', 'Prestation supprimée.');
    }

    public function updateReview(Request $request, AvisClient $avis): RedirectResponse
    {
        $data = $request->validate([
            'prenom_nom' => ['required', 'string', 'max:255'],
            'commentaire' => ['required', 'string', 'max:2000'],
            'satisfaction' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $avis->update($data);

        return back()->with('success', 'Avis mis à jour.');
    }

    public function destroyReview(AvisClient $avis): RedirectResponse
    {
        $avis->delete();

        return back()->with('success', 'Avis supprimé.');
    }

    public function uploadInvoice(Request $request, Client $client): RedirectResponse
    {
        $request->validate([
            'invoice' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $file = $request->file('invoice');
        // Chemin identique à l'ancien site (includes/admin-accounts.php) :
        // "invoices/<client_id>/<fichier>", relatif à storage/ — pas "factures/".
        $path = $file->store('invoices/'.$client->id, 'local');

        $client->factures()->create([
            'nom_fichier' => $file->getClientOriginalName(),
            'chemin_fichier' => $path,
            'mime_type' => 'application/pdf',
            'taille_octets' => $file->getSize(),
        ]);

        return back()->with('success', 'Facture envoyée.');
    }

    public function viewInvoice(int $factureId)
    {
        $facture = \App\Models\Facture::findOrFail($factureId);

        return Storage::disk('local')->response(
            $facture->chemin_fichier,
            $facture->nom_fichier,
            ['Content-Type' => $facture->mime_type, 'Content-Disposition' => 'inline']
        );
    }
}
