<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\AvisClient;
use App\Models\Client;
use App\Models\Compte;
use App\Models\ContactMessage;
use App\Models\Prestation;
use App\Models\RssSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
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
        // whereHas('compte') : filet de sécurité contre un client orphelin
        // (compte_id pointant vers un compte déjà supprimé) — accountRow()
        // plante sinon sur compte->email. Ne devrait plus arriver depuis le
        // correctif de destroyAccount, mais évite de casser tout le
        // dashboard si un cas survient quand même.
        $clients = Client::with(['compte', 'prestations', 'factures'])
            ->whereHas('compte')
            ->orderBy('nom_complet')
            ->get()
            ->map(fn (Client $c) => $this->accountRow($c));

        $rss = RssSubscriber::orderByDesc('date_creation')->get(['id', 'email', 'date_creation']);

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

        // Chiffre d'affaires = prestations effectivement facturées ou réglées
        // (on exclut "En cours"/"En attente"/"Dispensé" du total encaissé).
        $chiffreAffaires = (float) Prestation::whereIn('statut_facturation', ['Payé', 'Facturé'])->sum('prix');

        $nouveauxClientsMois = Client::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        $satisfactionMoyenne = AvisClient::where('satisfaction', '>', 0)->avg('satisfaction');

        $articles = Article::orderByDesc('published_at')->get([
            'id', 'title', 'slug', 'excerpt', 'content', 'image', 'published_at',
        ]);

        // Remplace la base Notion "ERP" : suivi des messages du formulaire
        // de contact (voir ContactController::store et
        // AdminController::updateContactStatus).
        $contacts = ContactMessage::orderByDesc('date_creation')->get([
            'id', 'client_id', 'prenom', 'nom', 'nom_entreprise', 'email', 'telephone',
            'sujet', 'message', 'prise_de_contact_ok', 'date_creation',
        ]);

        return Inertia::render('Admin/Index', [
            'kpis' => [
                'comptes' => $clients->count(),
                'abonnes_rss' => $rss->count(),
                'factures_en_attente' => Prestation::where('statut_facturation', 'En attente')->count(),
                'chiffre_affaires' => $chiffreAffaires,
                'nouveaux_clients_mois' => $nouveauxClientsMois,
                'satisfaction_moyenne' => $satisfactionMoyenne !== null ? round((float) $satisfactionMoyenne, 1) : null,
                'contacts_en_attente' => $contacts->where('prise_de_contact_ok', false)->count(),
            ],
            'billingBreakdown' => $billingBreakdown,
            'roleBreakdown' => $roleBreakdown,
            'accounts' => $clients,
            'rssSubscribers' => $rss,
            'reviews' => $reviews,
            'articles' => $articles,
            'contacts' => $contacts,
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
            'job_domaine' => $c->job_domaine,
            'linkedin' => $c->linkedin,
            'role' => $c->role(),
            'derniere_connexion' => $c->compte->derniere_connexion,
            'photo_url' => $c->photo_path ? route('avatar', $c->id) : null,
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

    /**
     * Supprime un compte client depuis le dashboard.
     *
     * Attention : on ne compte PAS sur les cascades de clés étrangères
     * définies dans les migrations (clients.compte_id ->cascadeOnDelete,
     * etc.) — la base de prod a été reprise de l'ancien site et ces
     * contraintes n'y sont pas forcément réellement actives. On supprime
     * donc chaque table explicitement, dans l'ordre (enfants d'abord), pour
     * ne jamais laisser de client orphelin (compte_id pointant vers un
     * compte supprimé, qui plantait accountRow() avec compte === null).
     * avis_clients et contact_siteweb sont détachés (client_id => null)
     * plutôt que supprimés, pour garder l'historique.
     */
    public function destroyAccount(Request $request, Client $client): RedirectResponse
    {
        if ($client->compte_id === $request->user()->id) {
            return back()->with('error', 'Impossible de supprimer son propre compte.');
        }

        if ($client->role() === 'admin' && Client::whereNull('type_client')->count() <= 1) {
            return back()->with('error', 'Impossible de supprimer le dernier compte admin.');
        }

        if ($client->photo_path) {
            Storage::disk('local')->delete($client->photo_path);
        }

        foreach ($client->factures as $facture) {
            Storage::disk('local')->delete($facture->chemin_fichier);
            $facture->delete();
        }

        $client->prestations()->delete();

        AvisClient::where('client_id', $client->id)->update(['client_id' => null]);
        ContactMessage::where('client_id', $client->id)->update(['client_id' => null]);

        $compte = $client->compte;
        $client->delete();
        $compte?->delete();

        return back()->with('success', 'Compte supprimé.');
    }

    public function updateProfile(Request $request, Client $client): RedirectResponse
    {
        $data = $request->validate([
            'nom_complet' => ['nullable', 'string', 'max:255'],
            'nom_entreprise' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'location' => ['nullable', 'string', 'max:500'],
            'job_domaine' => ['nullable', 'string', 'max:255'],
            'linkedin' => ['nullable', 'string', 'max:500'],
            'commandes_libres' => ['nullable', 'string'],
        ]);

        $client->update($data);

        return back()->with('success', 'Fiche mise à jour.');
    }

    public function uploadPhoto(Request $request, Client $client): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
        ]);

        $path = $request->file('photo')->store('avatars', 'local');

        if ($client->photo_path) {
            Storage::disk('local')->delete($client->photo_path);
        }

        $client->update([
            'photo_path' => $path,
            'photo_mime' => $request->file('photo')->getMimeType(),
        ]);

        return back()->with('success', 'Photo mise à jour.');
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

    /**
     * Coche/décoche "prise de contact ok" sur un message de contact.
     * Remplace le suivi manuel qui se faisait dans la base Notion "ERP" :
     * quand la case passe de faux à vrai, on invite automatiquement la
     * personne à créer son compte (voir sendRegistrationInvite ci-dessous).
     */
    public function updateContactStatus(Request $request, ContactMessage $contact): RedirectResponse
    {
        $data = $request->validate([
            'prise_de_contact_ok' => ['required', 'boolean'],
            // Obligatoire quand on coche : sans ça, le compte auto-créé se
            // retrouve avec type_client = NULL, ce qui équivaut à "admin"
            // dans Client::role() — faille corrigée ici (voir aussi
            // sendRegistrationInvite ci-dessous).
            'type_client' => ['required_if:prise_de_contact_ok,true', 'nullable', 'in:Entreprise,Particulier'],
        ]);

        $wasOk = $contact->prise_de_contact_ok;
        $contact->update(['prise_de_contact_ok' => $data['prise_de_contact_ok']]);

        if (!$wasOk && $data['prise_de_contact_ok']) {
            $this->sendRegistrationInvite($contact, $data['type_client']);
        }

        return back()->with('success', 'Contact mis à jour.');
    }

    /**
     * Supprime une demande de contact (bouton "Supprimer" de l'onglet
     * Contacts). Ne touche pas au compte/client déjà créé le cas échéant.
     */
    public function destroyContact(ContactMessage $contact): RedirectResponse
    {
        $contact->delete();

        return back()->with('success', 'Message de contact supprimé.');
    }

    /**
     * Crée (si besoin) le compte du contact et lui envoie un lien pour
     * définir son mot de passe — même mécanisme que "mot de passe oublié"
     * (voir PasswordResetController), pas d'auto-inscription publique
     * (règle métier inchangée, voir routes/web.php) : c'est bien l'admin,
     * en cochant la case, qui déclenche la création du compte.
     *
     * Lien valable 7 jours (pas 1h comme un vrai reset) : un prospect
     * n'ouvre pas forcément l'email dans l'heure qui suit.
     */
    private function sendRegistrationInvite(ContactMessage $contact, string $typeClient): void
    {
        if (!$contact->client_id) {
            $compte = Compte::firstOrCreate(
                ['email' => $contact->email],
                ['mot_de_passe_hash' => Hash::make(Str::random(32))]
            );

            // $typeClient (Entreprise/Particulier, choisi par l'admin dans le
            // popup de l'onglet Contacts) n'est utilisé que si on crée
            // vraiment un nouveau client : si un compte/client existait déjà
            // pour cet email, on ne touche pas à son rôle actuel.
            $client = $compte->client ?? Client::create([
                'compte_id' => $compte->id,
                'nom_complet' => trim($contact->prenom.' '.($contact->nom ?? '')),
                'nom_entreprise' => $contact->nom_entreprise,
                'telephone' => $contact->telephone,
                'type_client' => $typeClient,
            ]);

            $contact->update(['client_id' => $client->id]);
        }

        $compte = $contact->client->compte;

        $token = Str::random(64);
        $compte->forceFill([
            'reset_token' => $token,
            'reset_token_expiry' => now()->addDays(7),
        ])->save();

        $webhook = config('services.n8n.auth_webhook_url');
        if ($webhook) {
            try {
                Http::timeout(10)->post($webhook, [
                    'event' => 'welcome',
                    'email' => $compte->email,
                    'name' => $contact->prenom,
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

    /**
     * Bouton "demander un avis" sur la fiche client — remplace le
     * déclenchement hebdomadaire automatique de l'ancien workflow Notion
     * (SEND Avis) : c'est désormais Thomas qui décide du bon moment.
     */
    public function requestReview(Client $client): RedirectResponse
    {
        $webhook = config('services.n8n.avis_webhook_url');
        if ($webhook) {
            try {
                Http::timeout(10)->post($webhook, [
                    'event' => 'review_requested',
                    'email' => $client->compte->email,
                    'name' => $client->nom_complet,
                    // Remplace l'ancien lien vers le formulaire Notion : le
                    // client laisse maintenant son avis depuis son espace
                    // (voir DashboardController::updateReview). S'il n'est
                    // pas connecté, la route /dashboard le renvoie login
                    // puis revient ici — comportement standard Laravel.
                    'dashboard_url' => route('dashboard'),
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return back()->with('success', "Demande d'avis envoyée.");
    }

    public function storeRssSubscriber(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:rss_subscriber,email'],
        ]);

        RssSubscriber::create($data);

        return back()->with('success', 'Abonné RSS ajouté.');
    }

    public function destroyRssSubscriber(RssSubscriber $subscriber): RedirectResponse
    {
        $subscriber->delete();

        return back()->with('success', 'Abonné RSS retiré.');
    }
}
