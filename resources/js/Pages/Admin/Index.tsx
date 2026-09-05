import { Head, router, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { useTranslation } from '../../hooks/useTranslation';
import DashboardLayout from '../../Layouts/DashboardLayout';
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

      <div className="flex flex-wrap gap-2 border-b border-line pb-4">
        {TABS.map((tb) => (
          <button
            key={tb}
            onClick={() => setTab(tb)}
            className={`rounded-full px-4 py-2 text-sm font-medium ${tab === tb ? 'bg-signal text-on-accent' : 'border border-line-strong text-ink-fade'}`}
          >
            {t(`admin.tab_${tb === 'overview' ? 'overview' : tb}`)}
          </button>
        ))}
      </div>

      {tab === 'overview' && (
        <div className="space-y-6">
          <div className="grid gap-4 sm:grid-cols-3">
            <Kpi label={t('admin.tab_accounts')} value={kpis.comptes} />
            <Kpi label={t('admin.tab_rss')} value={kpis.abonnes_rss} />
            <Kpi label="Factures en attente" value={kpis.factures_en_attente} />
          </div>
          <div className="grid gap-6 sm:grid-cols-2">
            <div className="rounded-2xl border border-line bg-paper p-6">
              <h3 className="font-display text-sm font-semibold text-ink">Répartition facturation</h3>
              <ul className="mt-4 space-y-2">
                {STATUTS.map((s) => (
                  <BarRow key={s} label={s} value={billingBreakdown[s] ?? 0} max={Math.max(1, ...Object.values(billingBreakdown))} />
                ))}
              </ul>
            </div>
            <div className="rounded-2xl border border-line bg-paper p-6">
              <h3 className="font-display text-sm font-semibold text-ink">Répartition des rôles</h3>
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
        <div className="space-y-4">
          <div className="flex flex-wrap items-center justify-between gap-3">
            <input placeholder="Rechercher..." value={search} onChange={(e) => setSearch(e.target.value)} className="input max-w-xs" />
            <NewClientForm />
          </div>
          <div className="overflow-x-auto rounded-2xl border border-line bg-paper">
            <table className="w-full min-w-[900px] text-left text-sm">
              <thead className="border-b border-line text-xs uppercase tracking-wide text-ink-fade">
                <tr>
                  <th className="px-4 py-3">Nom</th>
                  <th className="px-4 py-3">Email</th>
                  <th className="px-4 py-3">Entreprise</th>
                  <th className="px-4 py-3">Rôle</th>
                  <th className="px-4 py-3">Dernière connexion</th>
                  <th className="px-4 py-3">{t('admin.details_btn')}</th>
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
        <div className="overflow-x-auto rounded-2xl border border-line bg-paper">
          <table className="w-full text-left text-sm">
            <thead className="border-b border-line text-xs uppercase tracking-wide text-ink-fade">
              <tr><th className="px-4 py-3">Email</th><th className="px-4 py-3">Inscrit le</th></tr>
            </thead>
            <tbody>
              {rssSubscribers.map((s) => (
                <tr key={s.email} className="border-b border-line last:border-0">
                  <td className="px-4 py-3">{s.email}</td>
                  <td className="px-4 py-3 text-ink-fade">{new Date(s.date_creation).toLocaleDateString('fr-FR')}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {tab === 'invoices' && (
        <div className="space-y-4">
          {accounts.filter((a) => a.factures.length > 0 || a.prestations.length > 0).map((a) => (
            <div key={a.id} className="rounded-2xl border border-line bg-paper p-5">
              <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                  <div className="font-semibold text-ink">{a.nom_complet}</div>
                  <div className="text-xs text-ink-fade">{a.email}</div>
                </div>
                <UploadInvoiceForm clientId={a.id} />
              </div>
              {a.factures.length > 0 && (
                <ul className="mt-3 space-y-1 text-sm">
                  {a.factures.map((f) => (
                    <li key={f.id}>
                      <a href={`/admin/factures/${f.id}`} target="_blank" rel="noreferrer" className="text-signal-deep dark:text-signal">{f.nom_fichier}</a>
                    </li>
                  ))}
                </ul>
              )}
            </div>
          ))}
        </div>
      )}

      {tab === 'reviews' && (
        <div className="space-y-3">
          {reviews.length === 0 && <p className="text-sm text-ink-fade">{t('admin.no_reviews')}</p>}
          {reviews.map((r) => (
            <ReviewRow key={r.id} review={r} />
          ))}
        </div>
      )}

      {flash.success && <div className="fixed bottom-6 right-6 rounded-lg bg-success px-4 py-2 text-sm text-white shadow-lg">{flash.success}</div>}

      <style>{`
        .input { border:1px solid rgb(var(--c-line)); border-radius:0.65rem; padding:0.5rem 0.8rem; background:rgb(var(--c-mist)); color:rgb(var(--c-ink)); font-size:0.85rem; }
        .btn-primary { border-radius:9999px; background:rgb(var(--c-signal)); color:rgb(var(--c-on-accent)); font-size:0.75rem; font-weight:600; padding:0.4rem 0.9rem; }
        .btn-ghost { border-radius:9999px; border:1px solid rgb(var(--c-line-strong)); color:rgb(var(--c-ink)); font-size:0.75rem; font-weight:600; padding:0.4rem 0.9rem; }
      `}</style>
    </>
  );
}

function Kpi({ label, value }: { label: string; value: number }) {
  return (
    <div className="rounded-2xl border border-line bg-paper p-5">
      <div className="font-display text-2xl font-bold text-ink">{value}</div>
      <div className="mt-1 text-xs text-ink-fade">{label}</div>
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
      <tr className="border-b border-line">
        <td className="px-4 py-3 font-medium text-ink">{account.nom_complet}</td>
        <td className="px-4 py-3 text-ink-fade">{account.email}</td>
        <td className="px-4 py-3 text-ink-fade">{account.nom_entreprise ?? '—'}</td>
        <td className="px-4 py-3">
          <select
            value={roleForm.data.role}
            onChange={(e) => {
              roleForm.setData('role', e.target.value as Account['role']);
              router.patch(`/admin/comptes/${account.id}`, { role: e.target.value });
            }}
            className="input"
          >
            <option value="particulier">Particulier</option>
            <option value="entreprise">Entreprise</option>
            <option value="admin">Admin</option>
          </select>
        </td>
        <td className="px-4 py-3 text-ink-fade">{account.derniere_connexion ? new Date(account.derniere_connexion).toLocaleString('fr-FR') : '—'}</td>
        <td className="px-4 py-3">
          <button onClick={onToggle} className="btn-ghost">{expanded ? 'Fermer' : 'Détails'}</button>
        </td>
      </tr>
      {expanded && (
        <tr className="border-b border-line bg-mist/50">
          <td colSpan={6} className="px-4 py-4">
            <div className="space-y-3">
              <h4 className="text-xs font-semibold uppercase tracking-wide text-ink-fade">Prestations</h4>
              {account.prestations.map((p) => (
                <div key={p.id} className="flex flex-wrap items-center gap-3 rounded-lg border border-line bg-paper px-3 py-2 text-sm">
                  <span className="font-medium">{p.type_service ?? '—'}</span>
                  <span className="text-ink-fade">{p.prix ? `${p.prix} €` : ''}</span>
                  <span className="rounded-full bg-line px-2 py-0.5 text-xs">{p.statut_facturation}</span>
                  <button onClick={() => router.delete(`/admin/prestations/${p.id}`)} className="ml-auto text-xs text-danger">{'Supprimer'}</button>
                </div>
              ))}
              <form
                onSubmit={(e) => { e.preventDefault(); prestationForm.post(`/admin/comptes/${account.id}/prestations`); }}
                className="flex flex-wrap items-end gap-2"
              >
                <input placeholder="Service" value={prestationForm.data.type_service} onChange={(e) => prestationForm.setData('type_service', e.target.value)} className="input" />
                <input placeholder="Prix" type="number" value={prestationForm.data.prix} onChange={(e) => prestationForm.setData('prix', e.target.value)} className="input w-24" />
                <select value={prestationForm.data.statut_facturation} onChange={(e) => prestationForm.setData('statut_facturation', e.target.value)} className="input">
                  {STATUTS.map((s) => <option key={s} value={s}>{s}</option>)}
                </select>
                <button type="submit" className="btn-primary">+ Ajouter</button>
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
    return <button onClick={() => setOpen(true)} className="btn-primary">{'+ Nouveau client'}</button>;
  }

  return (
    <form
      onSubmit={(e) => { e.preventDefault(); post('/admin/comptes', { onSuccess: () => { reset(); setOpen(false); } }); }}
      className="flex flex-wrap items-end gap-2 rounded-xl border border-line bg-paper p-3"
    >
      <input required placeholder="Nom complet" value={data.nom_complet} onChange={(e) => setData('nom_complet', e.target.value)} className="input" />
      <input required type="email" placeholder="Email" value={data.email} onChange={(e) => setData('email', e.target.value)} className="input" />
      <input placeholder="Entreprise" value={data.nom_entreprise} onChange={(e) => setData('nom_entreprise', e.target.value)} className="input" />
      <select value={data.role} onChange={(e) => setData('role', e.target.value)} className="input">
        <option value="particulier">Particulier</option>
        <option value="entreprise">Entreprise</option>
        <option value="admin">Admin</option>
      </select>
      <button type="submit" disabled={processing} className="btn-primary">Créer</button>
      <button type="button" onClick={() => setOpen(false)} className="btn-ghost">Annuler</button>
    </form>
  );
}

function UploadInvoiceForm({ clientId }: { clientId: number }) {
  const { setData, post, processing } = useForm<{ invoice: File | null }>({ invoice: null });

  return (
    <form
      onSubmit={(e) => { e.preventDefault(); post(`/admin/comptes/${clientId}/factures`, { forceFormData: true }); }}
      className="flex items-center gap-2"
    >
      <input type="file" accept="application/pdf" onChange={(e) => setData('invoice', e.target.files?.[0] ?? null)} className="text-xs" />
      <button type="submit" disabled={processing} className="btn-primary">Envoyer</button>
    </form>
  );
}

function ReviewRow({ review }: { review: { id: number; prenom_nom: string; satisfaction: number; commentaire: string } }) {
  const form = useForm({ prenom_nom: review.prenom_nom, commentaire: review.commentaire, satisfaction: review.satisfaction });

  return (
    <form
      onSubmit={(e) => { e.preventDefault(); form.patch(`/admin/avis/${review.id}`); }}
      className="flex flex-wrap items-center gap-3 rounded-xl border border-line bg-paper p-4"
    >
      <input value={form.data.prenom_nom} onChange={(e) => form.setData('prenom_nom', e.target.value)} className="input w-40" />
      <input value={form.data.commentaire} onChange={(e) => form.setData('commentaire', e.target.value)} className="input flex-1" />
      <select value={form.data.satisfaction} onChange={(e) => form.setData('satisfaction', Number(e.target.value))} className="input w-20">
        {[1, 2, 3, 4, 5].map((n) => <option key={n} value={n}>{n}★</option>)}
      </select>
      <button type="submit" className="btn-primary">Enregistrer</button>
      <button type="button" onClick={() => router.delete(`/admin/avis/${review.id}`)} className="btn-ghost text-danger">Supprimer</button>
    </form>
  );
}

AdminIndex.layout = (page: React.ReactNode) => <Wrapper>{page}</Wrapper>;
function Wrapper({ children }: { children: React.ReactNode }) {
  const { t } = useTranslation();
  return <DashboardLayout title={t('admin.title')}>{children}</DashboardLayout>;
}

export default AdminIndex;
