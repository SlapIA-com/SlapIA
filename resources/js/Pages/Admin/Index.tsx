import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { useTranslation } from '../../hooks/useTranslation';
import type { SharedProps } from '../../types';

interface Prestation {
  id: number;
  type_service: string | null;
  prix: string | null;
  statut_facturation: string;
  description: string | null;
  date_debut: string | null;
  date_fin: string | null;
}

interface Account {
  id: number;
  nom_complet: string;
  email: string;
  nom_entreprise: string | null;
  telephone: string | null;
  location: string | null;
  role: 'admin' | 'entreprise' | 'particulier';
  derniere_connexion: string | null;
  prestations: Prestation[];
  factures: Array<{ id: number; nom_fichier: string }>;
}

const STATUTS = ['Facturé', 'Payé', 'En cours', 'En attente', 'Dispensé'];
const TABS = ['overview', 'accounts', 'rss', 'invoices', 'reviews'] as const;
type Tab = (typeof TABS)[number];

function AdminIndex({
  kpis,
  billingBreakdown,
  roleBreakdown,
  accounts,
  rssSubscribers,
  reviews,
}: {
  kpis: { comptes: number; abonnes_rss: number; factures_en_attente: number };
  billingBreakdown: Record<string, number>;
  roleBreakdown: { particulier: number; entreprise: number; admin: number };
  accounts: Account[];
  rssSubscribers: Array<{ email: string; date_creation: string }>;
  reviews: Array<{ id: number; client_id: number | null; prenom_nom: string; satisfaction: number; commentaire: string; created_at: string }>;
}) {
  const { t } = useTranslation();
  const { flash } = usePage<SharedProps>().props;
  const [tab, setTab] = useState<Tab>('overview');
  const [search, setSearch] = useState('');
  const [expanded, setExpanded] = useState<number | null>(null);

  const filteredAccounts = useMemo(() => {
    const q = search.toLowerCase();
    return accounts.filter((a) => a.nom_complet.toLowerCase().includes(q) || a.email.toLowerCase().includes(q) || (a.nom_entreprise ?? '').toLowerCase().includes(q));
  }, [accounts, search]);

  return (
    <>
      <Head title={t('admin.title')} />

      <section className="section">
        <div className="container">
          <h1 className="page-hero__title">{t('admin.title')}</h1>

          <nav className="admin-tabs">
            {TABS.map((tb) => (
              <button
                key={tb}
                onClick={() => setTab(tb)}
                className={`admin-tab-btn${tab === tb ? ' is-active' : ''}`}
              >
                {t(`admin.tab_${tb === 'overview' ? 'overview' : tb}`)}
              </button>
            ))}
          </nav>

          {tab === 'overview' && (
            <div className="admin-tab-panel is-active">
              <div className="admin-kpi-row">
                <Kpi label={t('admin.tab_accounts')} value={kpis.comptes} />
                <Kpi label={t('admin.tab_rss')} value={kpis.abonnes_rss} />
                <Kpi label="Factures en attente" value={kpis.factures_en_attente} />
              </div>
              <div className="admin-chart-grid">
                <div className="admin-chart-card">
                  <h3>Répartition facturation</h3>
                  <ul className="mt-4 space-y-2">
                    {STATUTS.map((s) => (
                      <BarRow key={s} label={s} value={billingBreakdown[s] ?? 0} max={Math.max(1, ...Object.values(billingBreakdown))} />
                    ))}
                  </ul>
                </div>
                <div className="admin-chart-card">
                  <h3>Répartition des rôles</h3>
                  <ul className="mt-4 space-y-2">
                    <BarRow label="Particulier" value={roleBreakdown.particulier} max={Math.max(1, roleBreakdown.particulier, roleBreakdown.entreprise, roleBreakdown.admin)} />
                    <BarRow label="Entreprise" value={roleBreakdown.entreprise} max={Math.max(1, roleBreakdown.particulier, roleBreakdown.entreprise, roleBreakdown.admin)} />
                    <BarRow label="Admin" value={roleBreakdown.admin} max={Math.max(1, roleBreakdown.particulier, roleBreakdown.entreprise, roleBreakdown.admin)} />
                  </ul>
                </div>
              </div>
            </div>
          )}

          {tab === 'accounts' && (
            <div className="admin-tab-panel is-active">
              <div className="admin-toolbar-row">
                <div className="field admin-toolbar-row__search">
                  <input placeholder="Rechercher..." value={search} onChange={(e) => setSearch(e.target.value)} />
                </div>
                <NewClientForm />
              </div>
              <div className="admin-table-wrap">
                <table className="admin-table">
                  <thead>
                    <tr>
                      <th>Nom</th>
                      <th>Email</th>
                      <th>Entreprise</th>
                      <th>Rôle</th>
                      <th>Dernière connexion</th>
                      <th>{t('admin.details_btn')}</th>
                    </tr>
                  </thead>
                  <tbody>
                    {filteredAccounts.map((a) => (
                      <AccountRow key={a.id} account={a} expanded={expanded === a.id} onToggle={() => setExpanded(expanded === a.id ? null : a.id)} />
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {tab === 'rss' && (
            <div className="admin-tab-panel is-active">
              <div className="admin-table-wrap">
                <table className="admin-table">
                  <thead>
                    <tr><th>Email</th><th>Inscrit le</th></tr>
                  </thead>
                  <tbody>
                    {rssSubscribers.map((s) => (
                      <tr key={s.email}>
                        <td>{s.email}</td>
                        <td>{new Date(s.date_creation).toLocaleDateString('fr-FR')}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {tab === 'invoices' && (
            <div className="admin-tab-panel is-active">
              {accounts.filter((a) => a.factures.length > 0 || a.prestations.length > 0).map((a) => (
                <div key={a.id} className="admin-invoice-card">
                  <div className="admin-inline-avatar" style={{ justifyContent: 'space-between' }}>
                    <div>
                      <div>{a.nom_complet}</div>
                      <div>{a.email}</div>
                    </div>
                    <UploadInvoiceForm clientId={a.id} />
                  </div>
                  {a.factures.length > 0 ? (
                    <ul className="admin-invoice-list">
                      {a.factures.map((f) => (
                        <li key={f.id}>
                          <a href={`/admin/factures/${f.id}`} target="_blank" rel="noreferrer">{f.nom_fichier}</a>
                        </li>
                      ))}
                    </ul>
                  ) : (
                    <p className="admin-invoice-empty">Aucune facture</p>
                  )}
                </div>
              ))}
            </div>
          )}

          {tab === 'reviews' && (
            <div className="admin-tab-panel is-active">
              {reviews.length === 0 && <p className="admin-invoice-empty">{t('admin.no_reviews')}</p>}
              {reviews.map((r) => (
                <ReviewRow key={r.id} review={r} />
              ))}
            </div>
          )}
        </div>
      </section>

      {flash.success && <div className="fixed bottom-6 right-6 rounded-lg bg-success px-4 py-2 text-sm text-white shadow-lg">{flash.success}</div>}
    </>
  );
}

function Kpi({ label, value }: { label: string; value: number }) {
  return (
    <div className="admin-kpi-card">
      <div className="num">{value}</div>
      <div className="label">{label}</div>
    </div>
  );
}

function BarRow({ label, value, max }: { label: string; value: number; max: number }) {
  return (
    <li>
      <div className="flex justify-between text-xs text-ink-fade"><span>{label}</span><span>{value}</span></div>
      <div className="mt-1 h-2 rounded-full bg-line"><div className="h-2 rounded-full bg-signal" style={{ width: `${(value / max) * 100}%` }} /></div>
    </li>
  );
}

function AccountRow({ account, expanded, onToggle }: { account: Account; expanded: boolean; onToggle: () => void }) {
  const roleForm = useForm({ role: account.role });
  const prestationForm = useForm({ type_service: '', prix: '', statut_facturation: 'En attente', description: '', date_debut: '', date_fin: '' });

  return (
    <>
      <tr>
        <td>{account.nom_complet}</td>
        <td>{account.email}</td>
        <td>{account.nom_entreprise ?? '—'}</td>
        <td>
          <select
            value={roleForm.data.role}
            onChange={(e) => {
              roleForm.setData('role', e.target.value as Account['role']);
              router.patch(`/admin/comptes/${account.id}`, { role: e.target.value });
            }}
          >
            <option value="particulier">Particulier</option>
            <option value="entreprise">Entreprise</option>
            <option value="admin">Admin</option>
          </select>
        </td>
        <td>{account.derniere_connexion ? new Date(account.derniere_connexion).toLocaleString('fr-FR') : '—'}</td>
        <td>
          <button onClick={onToggle} className="btn btn--ghost">{expanded ? 'Fermer' : 'Détails'}</button>
        </td>
      </tr>
      {expanded && (
        <tr className="admin-details-row">
          <td colSpan={6}>
            <div className="admin-prestations-section">
              <h4 className="admin-prestations-title">Prestations</h4>
              <div className="admin-prestations-list">
                {account.prestations.map((p) => (
                  <div key={p.id} className="admin-prestation-row admin-inline-avatar">
                    <span>{p.type_service ?? '—'}</span>
                    <span>{p.prix ? `${p.prix} €` : ''}</span>
                    <span>{p.statut_facturation}</span>
                    <button onClick={() => router.delete(`/admin/prestations/${p.id}`)} className="btn btn--danger" style={{ marginLeft: 'auto' }}>{'Supprimer'}</button>
                  </div>
                ))}
              </div>
              <form onSubmit={(e) => { e.preventDefault(); prestationForm.post(`/admin/comptes/${account.id}/prestations`); }}>
                <div className="admin-prestation-fields">
                  <div className="admin-details-field">
                    <label>Service</label>
                    <input value={prestationForm.data.type_service} onChange={(e) => prestationForm.setData('type_service', e.target.value)} />
                  </div>
                  <div className="admin-details-field">
                    <label>Prix</label>
                    <input type="number" value={prestationForm.data.prix} onChange={(e) => prestationForm.setData('prix', e.target.value)} />
                  </div>
                  <div className="admin-details-field">
                    <label>Statut</label>
                    <select value={prestationForm.data.statut_facturation} onChange={(e) => prestationForm.setData('statut_facturation', e.target.value)}>
                      {STATUTS.map((s) => <option key={s} value={s}>{s}</option>)}
                    </select>
                  </div>
                </div>
                <div className="admin-prestation-actions">
                  <button type="submit" className="btn btn--primary">+ Ajouter</button>
                </div>
              </form>
            </div>
          </td>
        </tr>
      )}
    </>
  );
}

function NewClientForm() {
  const [open, setOpen] = useState(false);
  const { data, setData, post, processing, reset } = useForm({
    nom_complet: '', email: '', nom_entreprise: '', job_domaine: '', linkedin: '', role: 'particulier', password: '',
  });

  if (!open) {
    return <button onClick={() => setOpen(true)} className="btn btn--primary">{'+ Nouveau client'}</button>;
  }

  return (
    <div className="admin-new-client-panel">
      <form onSubmit={(e) => { e.preventDefault(); post('/admin/comptes', { onSuccess: () => { reset(); setOpen(false); } }); }}>
        <div className="admin-prestation-fields">
          <div className="admin-details-field">
            <label>Nom complet</label>
            <input required value={data.nom_complet} onChange={(e) => setData('nom_complet', e.target.value)} />
          </div>
          <div className="admin-details-field">
            <label>Email</label>
            <input required type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />
          </div>
          <div className="admin-details-field">
            <label>Entreprise</label>
            <input value={data.nom_entreprise} onChange={(e) => setData('nom_entreprise', e.target.value)} />
          </div>
          <div className="admin-details-field">
            <label>Rôle</label>
            <select value={data.role} onChange={(e) => setData('role', e.target.value)}>
              <option value="particulier">Particulier</option>
              <option value="entreprise">Entreprise</option>
              <option value="admin">Admin</option>
            </select>
          </div>
        </div>
        <div className="admin-action-row">
          <button type="submit" disabled={processing} className="btn btn--primary">Créer</button>
          <button type="button" onClick={() => setOpen(false)} className="btn btn--ghost">Annuler</button>
        </div>
      </form>
    </div>
  );
}

function UploadInvoiceForm({ clientId }: { clientId: number }) {
  const { setData, post, processing } = useForm<{ invoice: File | null }>({ invoice: null });

  return (
    <form
      onSubmit={(e) => { e.preventDefault(); post(`/admin/comptes/${clientId}/factures`, { forceFormData: true }); }}
      className="admin-invoice-upload"
    >
      <input type="file" accept="application/pdf" onChange={(e) => setData('invoice', e.target.files?.[0] ?? null)} />
      <button type="submit" disabled={processing} className="btn btn--primary">Envoyer</button>
    </form>
  );
}

function ReviewRow({ review }: { review: { id: number; prenom_nom: string; satisfaction: number; commentaire: string } }) {
  const form = useForm({ prenom_nom: review.prenom_nom, commentaire: review.commentaire, satisfaction: review.satisfaction });

  return (
    <form
      onSubmit={(e) => { e.preventDefault(); form.patch(`/admin/avis/${review.id}`); }}
      className="admin-new-client-panel admin-inline-avatar"
    >
      <input value={form.data.prenom_nom} onChange={(e) => form.setData('prenom_nom', e.target.value)} />
      <input value={form.data.commentaire} onChange={(e) => form.setData('commentaire', e.target.value)} style={{ flex: '1 1 260px' }} />
      <select value={form.data.satisfaction} onChange={(e) => form.setData('satisfaction', Number(e.target.value))}>
        {[1, 2, 3, 4, 5].map((n) => <option key={n} value={n}>{n}★</option>)}
      </select>
      <button type="submit" className="btn btn--primary">Enregistrer</button>
      <button type="button" onClick={() => router.delete(`/admin/avis/${review.id}`)} className="btn btn--danger">Supprimer</button>
    </form>
  );
}

export default AdminIndex;
