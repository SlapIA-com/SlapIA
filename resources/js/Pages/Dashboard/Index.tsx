import { Head, useForm, usePage } from '@inertiajs/react';
import { useTranslation } from '../../hooks/useTranslation';
import type { SharedProps } from '../../types';

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

const BADGE_MODIFIERS: Record<string, string> = {
  'Payé': 'dash-badge--paid',
  'En attente': 'dash-badge--pending',
  'En cours': 'dash-badge--cours',
  'Facturé': 'dash-badge--invoiced',
  'Dispensé': 'dash-badge--dispense',
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
  const { flash } = usePage<SharedProps>().props;

  const passwordForm = useForm({ current_password: '', password: '', password_confirmation: '' });
  const linkedinForm = useForm({ linkedin: client.linkedin ?? '' });
  const contactForm = useForm({ telephone: client.telephone ?? '', location: client.location ?? '' });
  const reviewForm = useForm({ commentaire: avis?.commentaire ?? '', satisfaction: avis?.satisfaction ?? 5 });

  return (
    <>
      <Head title={t('dashboard.title')} />

      <section className="section">
        <div className="container">
          <h1 className="page-hero__title">{t('dashboard.title')}</h1>

          {flash.success && (
            <div className="alert alert--success">
              <span>✓</span>
              <span>{flash.success}</span>
            </div>
          )}
          {flash.error && (
            <div className="alert alert--error">
              <span>!</span>
              <span>{flash.error}</span>
            </div>
          )}

          <div className="dash-summary">
            <div>
              <div className="dash-summary__greeting">{client.nom_complet}</div>
              <p className="dash-summary__sub">{t('dashboard.greeting')}</p>
            </div>
            {prestation ? (
              <span className={`dash-badge ${BADGE_MODIFIERS[prestation.statut_facturation] ?? ''}`}>
                {prestation.statut_facturation}
              </span>
            ) : (
              <span className="dash-badge">{t('dashboard.no_service')}</span>
            )}
          </div>

          <div className="dash-grid">
            <div className="dash-card">
              <h2>{t('dashboard.label_profile')}</h2>
              <DashField label={t('dashboard.label_name')} value={client.nom_complet} />
              <DashField label={t('dashboard.label_email')} value={client.email} />
              {client.nom_entreprise && <DashField label={t('dashboard.label_company')} value={client.nom_entreprise} />}
              {client.derniere_connexion && (
                <DashField label={t('dashboard.label_last_login')} value={new Date(client.derniere_connexion).toLocaleString('fr-FR')} />
              )}

              <form onSubmit={(e) => { e.preventDefault(); contactForm.post('/dashboard/contact'); }}>
                <div className="dash-field-edit">
                  <label>{t('dashboard.label_phone')}</label>
                  <div className="dash-field-edit__row">
                    <input
                      value={contactForm.data.telephone}
                      onChange={(e) => contactForm.setData('telephone', e.target.value)}
                      placeholder={t('dashboard.placeholder_phone')}
                    />
                  </div>
                </div>
                <div className="dash-field-edit">
                  <label>{t('dashboard.label_location')}</label>
                  <div className="dash-field-edit__row">
                    <input
                      value={contactForm.data.location}
                      onChange={(e) => contactForm.setData('location', e.target.value)}
                      placeholder={t('dashboard.placeholder_location')}
                    />
                  </div>
                </div>
                <div className="dash-field-edit">
                  <label>{t('dashboard.label_linkedin')}</label>
                  <div className="dash-field-edit__row">
                    <input
                      value={linkedinForm.data.linkedin}
                      onChange={(e) => linkedinForm.setData('linkedin', e.target.value)}
                      placeholder={t('dashboard.placeholder_linkedin')}
                    />
                  </div>
                </div>
                <div className="dash-field-edit__row">
                  <button type="submit" className="btn btn--primary">{t('dashboard.save')}</button>
                  <button type="button" onClick={() => linkedinForm.post('/dashboard/linkedin')} className="btn btn--ghost">{t('dashboard.label_linkedin')}</button>
                </div>
              </form>
            </div>

            <div className="dash-card">
              <h2>{t('dashboard.change_password_title')}</h2>
              <form
                onSubmit={(e) => { e.preventDefault(); passwordForm.post('/dashboard/password', { onSuccess: () => passwordForm.reset() }); }}
                className="dash-password-form"
              >
                {passwordForm.errors.current_password && (
                  <div className="alert alert--error">
                    <span>!</span>
                    <span>{passwordForm.errors.current_password}</span>
                  </div>
                )}
                <div className="dash-field-edit">
                  <label>{t('dashboard.label_current_password')}</label>
                  <div className="dash-field-edit__row">
                    <input type="password" value={passwordForm.data.current_password} onChange={(e) => passwordForm.setData('current_password', e.target.value)} />
                  </div>
                </div>
                <div className="dash-field-edit">
                  <label>{t('dashboard.label_new_password')}</label>
                  <div className="dash-field-edit__row">
                    <input type="password" minLength={8} value={passwordForm.data.password} onChange={(e) => passwordForm.setData('password', e.target.value)} />
                  </div>
                </div>
                <div className="dash-field-edit">
                  <label>{t('dashboard.label_new_password')}</label>
                  <div className="dash-field-edit__row">
                    <input type="password" value={passwordForm.data.password_confirmation} onChange={(e) => passwordForm.setData('password_confirmation', e.target.value)} />
                  </div>
                </div>
                <button type="submit" className="btn btn--primary">{t('dashboard.submit_update')}</button>
              </form>
            </div>
          </div>

          <div className="dash-card">
            <h2>{t('dashboard.label_invoices')}</h2>
            {factures.length === 0 ? (
              <p className="dash-invoice-empty">{t('dashboard.empty_invoices')}</p>
            ) : (
              factures.map((f, i) => (
                <div key={f.id} className="dash-invoice-row">
                  <span className="dash-invoice-row__name">{f.nom_fichier}</span>
                  <a href={`/dashboard/factures/${i}`} target="_blank" rel="noreferrer" className="btn btn--ghost">{t('dashboard.view')}</a>
                </div>
              ))
            )}
          </div>

          <div className="dash-card">
            <h2>{t('dashboard.label_avis_title')}</h2>
            <div className="dash-avis-layout">
              <form className="dash-avis-form" onSubmit={(e) => { e.preventDefault(); reviewForm.post('/dashboard/review'); }}>
                <div className="dash-stars-input">
                  {[1, 2, 3, 4, 5].map((n) => (
                    <button
                      type="button"
                      key={n}
                      className={`dash-stars-input__star${n <= reviewForm.data.satisfaction ? ' dash-stars-input__star--filled' : ''}`}
                      onClick={() => reviewForm.setData('satisfaction', n)}
                    >
                      ★
                    </button>
                  ))}
                </div>
                <textarea
                  rows={4}
                  maxLength={2000}
                  value={reviewForm.data.commentaire}
                  onChange={(e) => reviewForm.setData('commentaire', e.target.value)}
                  placeholder={t('dashboard.placeholder_avis')}
                />
                <button type="submit" className="btn btn--primary">{t('dashboard.publish')}</button>
              </form>

              <div className="dash-avis-preview">
                <p className="dash-avis-preview__label">{t('dashboard.preview_label')}</p>
                {reviewForm.data.commentaire ? (
                  <div className="review-item">
                    <p className="review-text">« {reviewForm.data.commentaire} »</p>
                    <div className="review-stars">{'★'.repeat(reviewForm.data.satisfaction)}{'☆'.repeat(5 - reviewForm.data.satisfaction)}</div>
                  </div>
                ) : (
                  <p className="dash-avis-preview__empty">{t('dashboard.preview_empty')}</p>
                )}
              </div>
            </div>
          </div>
        </div>
      </section>
    </>
  );
}

function DashField({ label, value }: { label: string; value: string }) {
  return (
    <div className="dash-field">
      <span className="dash-field__label">{label}</span>
      <span className="dash-field__value">{value}</span>
    </div>
  );
}

export default Dashboard;
