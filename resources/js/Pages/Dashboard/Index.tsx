import { Head, useForm } from '@inertiajs/react';
import { useTranslation } from '../../hooks/useTranslation';
import DashboardLayout from '../../Layouts/DashboardLayout';

interface ClientData {
  nom_complet: string;
  email: string;
  nom_entreprise: string | null;
  telephone: string | null;
  location: string | null;
  linkedin: string | null;
  photo_url: string | null;
  derniere_connexion: string | null;
  commandes_libres: string | null;
}

interface Prestation {
  type_service: string | null;
  prix: string | null;
  statut_facturation: string;
}

const BADGE_COLORS: Record<string, string> = {
  'Facturé': 'bg-signal/15 text-signal-deep',
  'Payé': 'bg-success/15 text-success',
  'En cours': 'bg-warning/15 text-warning',
  'En attente': 'bg-line text-ink-fade',
  'Dispensé': 'bg-forest/15 text-forest',
};

function Dashboard({
  client,
  prestation,
  factures,
  avis,
}: {
  client: ClientData;
  prestation: Prestation | null;
  factures: Array<{ id: number; nom_fichier: string }>;
  avis: { commentaire: string; satisfaction: number } | null;
}) {
  const { t } = useTranslation();

  const passwordForm = useForm({ current_password: '', password: '', password_confirmation: '' });
  const linkedinForm = useForm({ linkedin: client.linkedin ?? '' });
  const contactForm = useForm({ telephone: client.telephone ?? '', location: client.location ?? '' });
  const reviewForm = useForm({ commentaire: avis?.commentaire ?? '', satisfaction: avis?.satisfaction ?? 5 });

  return (
    <>
      <Head title={t('dashboard.title')} />

      <div className="rounded-2xl border border-line bg-paper p-6">
        <p className="text-sm text-ink-fade">{t('dashboard.greeting')}</p>
        <h2 className="font-display text-xl font-semibold text-ink">{client.nom_complet}</h2>
        {prestation ? (
          <span className={`mt-3 inline-block rounded-full px-3 py-1 text-xs font-semibold ${BADGE_COLORS[prestation.statut_facturation] ?? 'bg-line text-ink-fade'}`}>
            {prestation.statut_facturation}
          </span>
        ) : (
          <p className="mt-3 text-sm text-ink-fade">{t('dashboard.no_service')}</p>
        )}
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <section className="rounded-2xl border border-line bg-paper p-6">
          <h3 className="font-display text-base font-semibold text-ink">{t('dashboard.label_profile')}</h3>
          <dl className="mt-4 space-y-2 text-sm">
            <Row label={t('dashboard.label_name')} value={client.nom_complet} />
            <Row label={t('dashboard.label_email')} value={client.email} />
            {client.nom_entreprise && <Row label={t('dashboard.label_company')} value={client.nom_entreprise} />}
            {client.derniere_connexion && <Row label={t('dashboard.label_last_login')} value={new Date(client.derniere_connexion).toLocaleString('fr-FR')} />}
          </dl>

          <form
            onSubmit={(e) => { e.preventDefault(); contactForm.post('/dashboard/contact'); }}
            className="mt-5 space-y-3 border-t border-line pt-5"
          >
            <Field label={t('dashboard.label_phone')}>
              <input value={contactForm.data.telephone} onChange={(e) => contactForm.setData('telephone', e.target.value)} placeholder={t('dashboard.placeholder_phone')} className="input" />
            </Field>
            <Field label={t('dashboard.label_location')}>
              <input value={contactForm.data.location} onChange={(e) => contactForm.setData('location', e.target.value)} placeholder={t('dashboard.placeholder_location')} className="input" />
            </Field>
            <Field label={t('dashboard.label_linkedin')}>
              <input value={linkedinForm.data.linkedin} onChange={(e) => linkedinForm.setData('linkedin', e.target.value)} placeholder={t('dashboard.placeholder_linkedin')} className="input" />
            </Field>
            <div className="flex gap-2">
              <button type="submit" className="btn-primary">{t('dashboard.save')}</button>
              <button type="button" onClick={() => linkedinForm.post('/dashboard/linkedin')} className="btn-ghost">{t('dashboard.label_linkedin')}</button>
            </div>
          </form>
        </section>

        <section className="rounded-2xl border border-line bg-paper p-6">
          <h3 className="font-display text-base font-semibold text-ink">{t('dashboard.change_password_title')}</h3>
          <form
            onSubmit={(e) => { e.preventDefault(); passwordForm.post('/dashboard/password', { onSuccess: () => passwordForm.reset() }); }}
            className="mt-4 space-y-3"
          >
            {passwordForm.errors.current_password && <p className="text-sm text-danger">{passwordForm.errors.current_password}</p>}
            <Field label={t('dashboard.label_current_password')}>
              <input type="password" value={passwordForm.data.current_password} onChange={(e) => passwordForm.setData('current_password', e.target.value)} className="input" />
            </Field>
            <Field label={t('dashboard.label_new_password')}>
              <input type="password" minLength={8} value={passwordForm.data.password} onChange={(e) => passwordForm.setData('password', e.target.value)} className="input" />
            </Field>
            <Field label={t('dashboard.label_new_password')}>
              <input type="password" value={passwordForm.data.password_confirmation} onChange={(e) => passwordForm.setData('password_confirmation', e.target.value)} className="input" />
            </Field>
            <button type="submit" className="btn-primary">{t('dashboard.submit_update')}</button>
          </form>
        </section>
      </div>

      <section className="rounded-2xl border border-line bg-paper p-6">
        <h3 className="font-display text-base font-semibold text-ink">{t('dashboard.label_invoices')}</h3>
        {factures.length === 0 ? (
          <p className="mt-3 text-sm text-ink-fade">{t('dashboard.empty_invoices')}</p>
        ) : (
          <ul className="mt-3 divide-y divide-line">
            {factures.map((f, i) => (
              <li key={f.id} className="flex items-center justify-between py-3 text-sm">
                <span className="text-ink-soft">{f.nom_fichier}</span>
                <a href={`/dashboard/factures/${i}`} target="_blank" rel="noreferrer" className="font-semibold text-signal-deep dark:text-signal">{t('dashboard.view')}</a>
              </li>
            ))}
          </ul>
        )}
      </section>

      <section className="rounded-2xl border border-line bg-paper p-6">
        <h3 className="font-display text-base font-semibold text-ink">{t('dashboard.label_avis_title')}</h3>
        <form onSubmit={(e) => { e.preventDefault(); reviewForm.post('/dashboard/review'); }} className="mt-4 grid gap-4 lg:grid-cols-2">
          <div className="space-y-3">
            <Field label={t('dashboard.label_satisfaction')}>
              <div className="flex gap-1 text-2xl text-signal-deep">
                {[1, 2, 3, 4, 5].map((n) => (
                  <button type="button" key={n} onClick={() => reviewForm.setData('satisfaction', n)}>
                    {n <= reviewForm.data.satisfaction ? '★' : '☆'}
                  </button>
                ))}
              </div>
            </Field>
            <Field label={t('dashboard.label_avis_text')}>
              <textarea rows={4} maxLength={2000} value={reviewForm.data.commentaire} onChange={(e) => reviewForm.setData('commentaire', e.target.value)} placeholder={t('dashboard.placeholder_avis')} className="input" />
            </Field>
            <button type="submit" className="btn-primary">{t('dashboard.publish')}</button>
          </div>
          <div className="rounded-xl border border-dashed border-line p-4">
            <p className="text-xs font-semibold uppercase tracking-wide text-ink-fade">{t('dashboard.preview_label')}</p>
            {reviewForm.data.commentaire ? (
              <div className="mt-3">
                <p className="text-sm text-ink-soft">« {reviewForm.data.commentaire} »</p>
                <div className="mt-2 text-signal-deep">{'★'.repeat(reviewForm.data.satisfaction)}{'☆'.repeat(5 - reviewForm.data.satisfaction)}</div>
              </div>
            ) : (
              <p className="mt-3 text-sm text-ink-fade">{t('dashboard.preview_empty')}</p>
            )}
          </div>
        </form>
      </section>

      <style>{`
        .input { width:100%; border:1px solid rgb(var(--c-line)); border-radius:0.65rem; padding:0.55rem 0.8rem; background:rgb(var(--c-mist)); color:rgb(var(--c-ink)); font-size:0.875rem; }
        .btn-primary { border-radius:9999px; background:rgb(var(--c-signal)); color:rgb(var(--c-on-accent)); font-size:0.8rem; font-weight:600; padding:0.5rem 1.1rem; }
        .btn-ghost { border-radius:9999px; border:1px solid rgb(var(--c-line-strong)); color:rgb(var(--c-ink)); font-size:0.8rem; font-weight:600; padding:0.5rem 1.1rem; }
      `}</style>
    </>
  );
}

function Row({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex justify-between gap-4">
      <dt className="text-ink-fade">{label}</dt>
      <dd className="font-medium text-ink">{value}</dd>
    </div>
  );
}

function Field({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <div>
      <label className="mb-1 block text-xs font-medium text-ink-fade">{label}</label>
      {children}
    </div>
  );
}

Dashboard.layout = (page: React.ReactNode) => <Wrapper>{page}</Wrapper>;
function Wrapper({ children }: { children: React.ReactNode }) {
  const { t } = useTranslation();
  return <DashboardLayout title={t('dashboard.title')}>{children}</DashboardLayout>;
}

export default Dashboard;
