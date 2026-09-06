import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
  ArcElement,
  BarElement,
  CategoryScale,
  Chart as ChartJS,
  Legend,
  LinearScale,
  Tooltip,
} from 'chart.js';
import { useMemo, useState } from 'react';
import { Bar, Doughnut } from 'react-chartjs-2';
import { useTranslation } from '../../hooks/useTranslation';
import type { SharedProps } from '../../types';

ChartJS.register(ArcElement, BarElement, CategoryScale, LinearScale, Tooltip, Legend);

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
  job_domaine: string | null;
  linkedin: string | null;
  role: 'admin' | 'entreprise' | 'particulier';
  derniere_connexion: string | null;
  photo_url: string | null;
  prestations: Prestation[];
  factures: Array<{ id: number; nom_fichier: string }>;
  commandes_libres: string | null;
}

const STATUTS = ['Facturé', 'Payé', 'En cours', 'En attente', 'Dispensé'];
const TABS = ['overview', 'accounts', 'rss', 'invoices', 'reviews', 'blog'] as const;
type Tab = (typeof TABS)[number];

interface BlogArticleRow {
  id: number;
  title: string;
  slug: string;
  excerpt: string | null;
  content: string | null;
  image: string | null;
  published_at: string;
}

function cssVar(name: string, fallback: string): string {
  if (typeof window === 'undefined') return fallback;
  const v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
  return v || fallback;
}

function initialsOf(name: string): string {
  const parts = name.trim().split(/\s+/).filter(Boolean);
  const letters = parts.slice(0, 2).map((p) => p[0]?.toUpperCase() ?? '');
  return letters.join('') || '?';
}

function Avatar({ clientId, name, size = 'sm' }: { clientId: number; name: string; size?: 'sm' | 'lg' }) {
  return (
    <span className={`admin-avatar-circle${size === 'lg' ? ' admin-avatar-circle--lg' : ''}`}>
      <img
        src={`/api/avatar/${clientId}`}
        alt=""
        loading="lazy"
        onError={(e) => {
          e.currentTarget.style.display = 'none';
          const sibling = e.currentTarget.nextElementSibling as HTMLElement | null;
          if (sibling) sibling.style.display = 'flex';
        }}
      />
      <span style={{ display: 'none' }}>{initialsOf(name)}</span>
    </span>
  );
}

function FileInput({
  accept,
  onChange,
  fileName,
  label,
}: {
  accept: string;
  onChange: (file: File | null) => void;
  fileName: string | null;
  label: string;
}) {
  return (
    <label className="admin-file-input">
      <span className="btn btn--ghost">{label}</span>
      <input type="file" accept={accept} onChange={(e) => onChange(e.target.files?.[0] ?? null)} />
      <span className="admin-file-input__name">{fileName ?? "Aucun fichier n'a été sélectionné"}</span>
    </label>
  );
}

function AdminIndex({
  kpis,
  billingBreakdown,
  roleBreakdown,
  accounts,
  rssSubscribers,
  reviews,
  articles,
}: {
  kpis: {
    comptes: number;
    abonnes_rss: number;
    factures_en_attente: number;
    chiffre_affaires: number;
    nouveaux_clients_mois: number;
    satisfaction_moyenne: number | null;
  };
  billingBreakdown: Record<string, number>;
  roleBreakdown: { particulier: number; entreprise: number; admin: number };
  accounts: Account[];
  rssSubscribers: Array<{ id: number; email: string; date_creation: string }>;
  reviews: Array<{ id: number; client_id: number | null; prenom_nom: string; satisfaction: number; commentaire: string; created_at: string }>;
  articles: BlogArticleRow[];
}) {
  const { t } = useTranslation();
  const { auth, flash } = usePage<SharedProps>().props;
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
                <Kpi label={t('admin.label_pending_invoices')} value={kpis.factures_en_attente} />
                <Kpi label={t('admin.label_revenue')} value={`${kpis.chiffre_affaires.toLocaleString('fr-FR', { maximumFractionDigits: 0 })} €`} />
                <Kpi label={t('admin.label_new_clients')} value={kpis.nouveaux_clients_mois} />
                <Kpi label={t('admin.label_avg_satisfaction')} value={kpis.satisfaction_moyenne !== null ? `${kpis.satisfaction_moyenne}/5` : '—'} />
              </div>

              {auth.user && (
                <div className="admin-new-client-panel" style={{ marginBottom: 32 }}>
                  <h3 style={{ marginBottom: 12 }}>{t('dashboard.label_photo')}</h3>
                  <OwnPhotoForm clientId={auth.user.id} name={auth.user.name} />
                </div>
              )}

              <div className="admin-chart-grid">
                <div className="admin-chart-card">
                  <h3>{t('admin.chart_billing_title')}</h3>
                  <div style={{ height: 260 }}>
                    <BillingChart billingBreakdown={billingBreakdown} />
                  </div>
                </div>
                <div className="admin-chart-card">
                  <h3>{t('admin.chart_role_title')}</h3>
                  <div style={{ height: 260 }}>
                    <RoleChart roleBreakdown={roleBreakdown} />
                  </div>
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
                      <th></th>
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
              <RssForm />
              <div className="admin-table-wrap">
                <table className="admin-table">
                  <thead>
                    <tr><th>Email</th><th>Inscrit le</th><th></th></tr>
                  </thead>
                  <tbody>
                    {rssSubscribers.map((s) => (
                      <tr key={s.id}>
                        <td>{s.email}</td>
                        <td>{new Date(s.date_creation).toLocaleDateString('fr-FR')}</td>
                        <td>
                          <button
                            onClick={() => {
                              if (confirm(t('admin.confirm_delete_rss'))) router.delete(`/admin/abonnes-rss/${s.id}`);
                            }}
                            className="btn btn--danger"
                          >
                            {t('admin.delete_btn')}
                          </button>
                        </td>
                      </tr>
                    ))}
                    {rssSubscribers.length === 0 && (
                      <tr><td colSpan={3} className="admin-invoice-empty">{t('admin.no_rss')}</td></tr>
                    )}
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
                    <div className="admin-inline-avatar">
                      <Avatar clientId={a.id} name={a.nom_complet} />
                      <div>
                        <div>{a.nom_complet}</div>
                        <div>{a.email}</div>
                      </div>
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

          {tab === 'blog' && (
            <div className="admin-tab-panel is-active">
              <NewArticleForm />
              {articles.length === 0 && <p className="admin-invoice-empty">{t('admin.no_articles')}</p>}
              {articles.map((a) => (
                <ArticleRow key={a.id} article={a} />
              ))}
            </div>
          )}
        </div>
      </section>

      {flash.success && <div className="fixed bottom-6 right-6 rounded-lg bg-success px-4 py-2 text-sm text-white shadow-lg">{flash.success}</div>}
    </>
  );
}

function Kpi({ label, value }: { label: string; value: number | string }) {
  return (
    <div className="admin-kpi-card">
      <div className="num">{value}</div>
      <div className="label">{label}</div>
    </div>
  );
}

function BillingChart({ billingBreakdown }: { billingBreakdown: Record<string, number> }) {
  const ink = cssVar('--ink-fade', '#726A82');
  const line = cssVar('--line', '#E1DCEB');
  const data = {
    labels: STATUTS,
    datasets: [
      {
        label: 'Prestations',
        data: STATUTS.map((s) => billingBreakdown[s] ?? 0),
        backgroundColor: cssVar('--signal', '#B36FE0'),
        borderRadius: 6,
      },
    ],
  };
  return (
    <Bar
      data={data}
      options={{
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { ticks: { color: ink }, grid: { display: false } },
          y: { ticks: { color: ink, precision: 0 }, grid: { color: line }, beginAtZero: true },
        },
      }}
    />
  );
}

function RoleChart({ roleBreakdown }: { roleBreakdown: { particulier: number; entreprise: number; admin: number } }) {
  const ink = cssVar('--ink-fade', '#726A82');
  const line = cssVar('--line', '#E1DCEB');
  const data = {
    labels: ['Particulier', 'Entreprise', 'Admin'],
    datasets: [
      {
        data: [roleBreakdown.particulier, roleBreakdown.entreprise, roleBreakdown.admin],
        backgroundColor: [cssVar('--signal', '#B36FE0'), cssVar('--forest', '#7A3F87'), cssVar('--signal-pink', '#E36FC4')],
        borderColor: line,
        borderWidth: 2,
      },
    ],
  };
  return (
    <Doughnut
      data={data}
      options={{
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { color: ink } } },
      }}
    />
  );
}

function OwnPhotoForm({ clientId, name }: { clientId: number; name: string }) {
  const { t } = useTranslation();
  const [fileName, setFileName] = useState<string | null>(null);
  const { setData, post, processing, reset } = useForm<{ photo: File | null }>({ photo: null });

  return (
    <form
      className="admin-photo-upload"
      onSubmit={(e) => {
        e.preventDefault();
        post('/dashboard/photo', { forceFormData: true, onSuccess: () => { reset(); setFileName(null); } });
      }}
    >
      <Avatar clientId={clientId} name={name} size="lg" />
      <FileInput
        accept="image/png,image/jpeg,image/webp"
        fileName={fileName}
        label={t('dashboard.change_photo')}
        onChange={(f) => { setData('photo', f); setFileName(f?.name ?? null); }}
      />
      <button type="submit" disabled={processing} className="btn btn--primary">{t('dashboard.change_photo')}</button>
    </form>
  );
}

function AccountRow({ account, expanded, onToggle }: { account: Account; expanded: boolean; onToggle: () => void }) {
  const roleForm = useForm({ role: account.role });
  const prestationForm = useForm({ type_service: '', prix: '', statut_facturation: 'En attente', description: '', date_debut: '', date_fin: '' });
  const profileForm = useForm({
    nom_complet: account.nom_complet,
    nom_entreprise: account.nom_entreprise ?? '',
    telephone: account.telephone ?? '',
    location: account.location ?? '',
    job_domaine: account.job_domaine ?? '',
    linkedin: account.linkedin ?? '',
    commandes_libres: account.commandes_libres ?? '',
  });
  const [photoFileName, setPhotoFileName] = useState<string | null>(null);
  const photoForm = useForm<{ photo: File | null }>({ photo: null });

  return (
    <>
      <tr>
        <td><Avatar clientId={account.id} name={account.nom_complet} /></td>
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
          <td colSpan={7}>
            <div className="admin-details-panel" style={{ flexDirection: 'column', alignItems: 'stretch' }}>
              <h4 className="admin-prestations-title">Informations client</h4>

              <form
                className="admin-photo-upload"
                onSubmit={(e) => {
                  e.preventDefault();
                  photoForm.post(`/admin/comptes/${account.id}/photo`, {
                    forceFormData: true,
                    onSuccess: () => { photoForm.reset(); setPhotoFileName(null); },
                  });
                }}
              >
                <Avatar clientId={account.id} name={account.nom_complet} size="lg" />
                <FileInput
                  accept="image/png,image/jpeg,image/webp"
                  fileName={photoFileName}
                  label="Changer la photo"
                  onChange={(f) => { photoForm.setData('photo', f); setPhotoFileName(f?.name ?? null); }}
                />
                <button type="submit" disabled={photoForm.processing} className="btn btn--primary">Envoyer</button>
              </form>

              <form
                onSubmit={(e) => { e.preventDefault(); profileForm.patch(`/admin/comptes/${account.id}/profil`); }}
              >
                <div className="admin-prestation-fields">
                  <div className="admin-details-field">
                    <label>Nom complet</label>
                    <input value={profileForm.data.nom_complet} onChange={(e) => profileForm.setData('nom_complet', e.target.value)} />
                  </div>
                  <div className="admin-details-field">
                    <label>Entreprise</label>
                    <input value={profileForm.data.nom_entreprise} onChange={(e) => profileForm.setData('nom_entreprise', e.target.value)} />
                  </div>
                  <div className="admin-details-field">
                    <label>Téléphone</label>
                    <input value={profileForm.data.telephone} onChange={(e) => profileForm.setData('telephone', e.target.value)} />
                  </div>
                  <div className="admin-details-field">
                    <label>Adresse</label>
                    <input value={profileForm.data.location} onChange={(e) => profileForm.setData('location', e.target.value)} />
                  </div>
                  <div className="admin-details-field">
                    <label>Poste / domaine</label>
                    <input value={profileForm.data.job_domaine} onChange={(e) => profileForm.setData('job_domaine', e.target.value)} />
                  </div>
                  <div className="admin-details-field">
                    <label>LinkedIn</label>
                    <input value={profileForm.data.linkedin} onChange={(e) => profileForm.setData('linkedin', e.target.value)} />
                  </div>
                  <div className="admin-details-field admin-details-field--wide">
                    <label>Commandes</label>
                    <textarea value={profileForm.data.commandes_libres} onChange={(e) => profileForm.setData('commandes_libres', e.target.value)} />
                  </div>
                </div>
                <div className="admin-prestation-actions">
                  <button type="submit" disabled={profileForm.processing} className="btn btn--primary">Enregistrer</button>
                </div>
              </form>
            </div>

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

function RssForm() {
  const { data, setData, post, processing, reset, errors } = useForm({ email: '' });

  return (
    <form
      className="admin-rss-form"
      onSubmit={(e) => { e.preventDefault(); post('/admin/abonnes-rss', { onSuccess: () => reset() }); }}
    >
      <div className="admin-details-field">
        <label>Email</label>
        <input type="email" required value={data.email} onChange={(e) => setData('email', e.target.value)} />
      </div>
      <button type="submit" disabled={processing} className="btn btn--primary">+ Ajouter un abonné</button>
      {errors.email && <span className="alert alert--error" style={{ flex: '1 1 220px' }}>{errors.email}</span>}
    </form>
  );
}

function UploadInvoiceForm({ clientId }: { clientId: number }) {
  const [fileName, setFileName] = useState<string | null>(null);
  const { setData, post, processing, reset } = useForm<{ invoice: File | null }>({ invoice: null });

  return (
    <form
      onSubmit={(e) => { e.preventDefault(); post(`/admin/comptes/${clientId}/factures`, { forceFormData: true, onSuccess: () => { reset(); setFileName(null); } }); }}
      className="admin-invoice-upload"
    >
      <FileInput accept="application/pdf" fileName={fileName} label="Choisir un fichier" onChange={(f) => { setData('invoice', f); setFileName(f?.name ?? null); }} />
      <button type="submit" disabled={processing} className="btn btn--primary">Envoyer</button>
    </form>
  );
}

function ReviewRow({ review }: { review: { id: number; prenom_nom: string; satisfaction: number; commentaire: string } }) {
  const form = useForm({ prenom_nom: review.prenom_nom, commentaire: review.commentaire, satisfaction: review.satisfaction });

  return (
    <form
      onSubmit={(e) => { e.preventDefault(); form.patch(`/admin/avis/${review.id}`); }}
      className="admin-new-client-panel"
    >
      <div className="admin-prestation-fields">
        <div className="admin-details-field">
          <label>Nom affiché</label>
          <input value={form.data.prenom_nom} onChange={(e) => form.setData('prenom_nom', e.target.value)} />
        </div>
        <div className="admin-details-field">
          <label>Satisfaction</label>
          <select value={form.data.satisfaction} onChange={(e) => form.setData('satisfaction', Number(e.target.value))}>
            {[1, 2, 3, 4, 5].map((n) => <option key={n} value={n}>{n}★</option>)}
          </select>
        </div>
      </div>
      <div className="admin-details-field admin-details-field--wide" style={{ marginTop: 10 }}>
        <label>Commentaire</label>
        <textarea
          className="admin-review-comment"
          value={form.data.commentaire}
          onChange={(e) => form.setData('commentaire', e.target.value)}
        />
      </div>
      <div className="admin-action-row" style={{ marginTop: 10 }}>
        <button type="submit" className="btn btn--primary">Enregistrer</button>
        <button type="button" onClick={() => router.delete(`/admin/avis/${review.id}`)} className="btn btn--danger">Supprimer</button>
      </div>
    </form>
  );
}

function NewArticleForm() {
  const { t } = useTranslation();
  const [open, setOpen] = useState(false);
  const { data, setData, post, processing, reset, errors } = useForm({
    title: '', excerpt: '', image: '', published_at: new Date().toISOString().slice(0, 10), content: '',
  });

  if (!open) {
    return <button onClick={() => setOpen(true)} className="btn btn--primary" style={{ marginBottom: 20 }}>{t('admin.add_article_btn')}</button>;
  }

  return (
    <div className="admin-new-client-panel" style={{ marginBottom: 20 }}>
      <form onSubmit={(e) => { e.preventDefault(); post('/admin/articles', { onSuccess: () => { reset(); setOpen(false); } }); }}>
        <div className="admin-prestation-fields">
          <div className="admin-details-field admin-details-field--wide">
            <label>{t('admin.label_article_title')}</label>
            <input required value={data.title} onChange={(e) => setData('title', e.target.value)} />
          </div>
          <div className="admin-details-field admin-details-field--wide">
            <label>{t('admin.label_article_excerpt')}</label>
            <input value={data.excerpt} onChange={(e) => setData('excerpt', e.target.value)} />
          </div>
          <div className="admin-details-field">
            <label>{t('admin.label_article_image')}</label>
            <input value={data.image} onChange={(e) => setData('image', e.target.value)} />
          </div>
          <div className="admin-details-field">
            <label>{t('admin.label_article_date')}</label>
            <input type="date" value={data.published_at} onChange={(e) => setData('published_at', e.target.value)} />
          </div>
          <div className="admin-details-field admin-details-field--wide">
            <label>{t('admin.label_article_content')}</label>
            <textarea rows={8} className="admin-review-comment" value={data.content} onChange={(e) => setData('content', e.target.value)} />
          </div>
        </div>
        {errors.title && <span className="alert alert--error">{errors.title}</span>}
        <div className="admin-action-row">
          <button type="submit" disabled={processing} className="btn btn--primary">{t('admin.save')}</button>
          <button type="button" onClick={() => setOpen(false)} className="btn btn--ghost">{t('admin.cancel_btn')}</button>
        </div>
      </form>
    </div>
  );
}

function ArticleRow({ article }: { article: BlogArticleRow }) {
  const { t } = useTranslation();
  const [open, setOpen] = useState(false);
  const form = useForm({
    title: article.title,
    excerpt: article.excerpt ?? '',
    image: article.image ?? '',
    published_at: article.published_at.slice(0, 10),
    content: article.content ?? '',
  });

  return (
    <div className="admin-invoice-card">
      <div className="admin-inline-avatar" style={{ justifyContent: 'space-between' }}>
        <div>
          <strong>{article.title}</strong>
          <div>{new Date(article.published_at).toLocaleDateString('fr-FR')} — /blog/{article.slug}</div>
        </div>
        <div className="admin-action-row">
          <button onClick={() => setOpen(!open)} className="btn btn--ghost">{open ? t('admin.close_btn') : t('admin.edit_btn')}</button>
          <button
            onClick={() => { if (confirm(t('admin.confirm_delete_article'))) router.delete(`/admin/articles/${article.id}`); }}
            className="btn btn--danger"
          >
            {t('admin.delete_btn')}
          </button>
        </div>
      </div>

      {open && (
        <form onSubmit={(e) => { e.preventDefault(); form.patch(`/admin/articles/${article.id}`); }} style={{ marginTop: 16 }}>
          <div className="admin-prestation-fields">
            <div className="admin-details-field admin-details-field--wide">
              <label>{t('admin.label_article_title')}</label>
              <input required value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} />
            </div>
            <div className="admin-details-field admin-details-field--wide">
              <label>{t('admin.label_article_excerpt')}</label>
              <input value={form.data.excerpt} onChange={(e) => form.setData('excerpt', e.target.value)} />
            </div>
            <div className="admin-details-field">
              <label>{t('admin.label_article_image')}</label>
              <input value={form.data.image} onChange={(e) => form.setData('image', e.target.value)} />
            </div>
            <div className="admin-details-field">
              <label>{t('admin.label_article_date')}</label>
              <input type="date" value={form.data.published_at} onChange={(e) => form.setData('published_at', e.target.value)} />
            </div>
            <div className="admin-details-field admin-details-field--wide">
              <label>{t('admin.label_article_content')}</label>
              <textarea rows={10} className="admin-review-comment" value={form.data.content} onChange={(e) => form.setData('content', e.target.value)} />
            </div>
          </div>
          <div className="admin-action-row" style={{ marginTop: 10 }}>
            <button type="submit" disabled={form.processing} className="btn btn--primary">{t('admin.save')}</button>
          </div>
        </form>
      )}
    </div>
  );
}

export default AdminIndex;
