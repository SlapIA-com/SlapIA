import { Head, Link, useForm } from '@inertiajs/react';
import { useTranslation } from '../hooks/useTranslation';
import { useReveal } from '../hooks/useReveal';

/** Port fidèle de pages/contact.php (mêmes classes CSS legacy/style.css). */
export default function Contact({ sent, subjects, turnstileSiteKey }: { sent: boolean; subjects: Record<string, string>; turnstileSiteKey: string }) {
  const { t } = useTranslation();
  const { data, setData, post, processing, errors } = useForm({
    firstname: '',
    lastname: '',
    email: '',
    company: '',
    subject: '',
    message: '',
    consent: false as boolean,
    'cf-turnstile-response': '',
  });
  const card = useReveal<HTMLDivElement>();
  const info = useReveal<HTMLDivElement>();

  function submit(e: React.FormEvent) {
    e.preventDefault();
    post('/contact');
  }

  return (
    <>
      <Head title={t('contact.meta_title')}>
        <meta name="description" content={t('contact.meta_description')} />
      </Head>

      <section className="page-hero">
        <div className="page-hero-canvas" aria-hidden="true">
          <span></span><span></span><span></span><span></span><span></span><span></span>
        </div>
        <div className="container">
          <span className="eyebrow">{t('contact.eyebrow')}</span>
          <h1 className="page-hero__title">
            {t('contact.title_pre')}<mark>{t('contact.title_mark')}</mark>{t('contact.title_post')}
          </h1>
          <p className="page-hero__lede">{t('contact.lede')}</p>
        </div>
      </section>

      <section className="section" style={{ paddingTop: 0 }}>
        <div className="container contact-layout">
          <div ref={card.ref} className={`contact-card ${card.className}`} style={card.style}>
            {sent && (
              <div className="alert alert--success">
                <span>✓</span>
                <span>{t('contact.success')}</span>
              </div>
            )}

            {Object.keys(errors).length > 0 && (
              <div className="alert alert--error">
                <span>!</span>
                <span>{Object.values(errors).join(' ')}</span>
              </div>
            )}

            <form onSubmit={submit}>
              <div className="form-grid">
                <div className="field">
                  <label htmlFor="firstname">{t('contact.label_firstname')}</label>
                  <input
                    type="text"
                    id="firstname"
                    required
                    value={data.firstname}
                    onChange={(e) => setData('firstname', e.target.value)}
                  />
                </div>
                <div className="field">
                  <label htmlFor="lastname">{t('contact.label_lastname')}</label>
                  <input
                    type="text"
                    id="lastname"
                    value={data.lastname}
                    onChange={(e) => setData('lastname', e.target.value)}
                  />
                </div>
                <div className="field">
                  <label htmlFor="email">{t('contact.label_email')}</label>
                  <input
                    type="email"
                    id="email"
                    required
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                  />
                </div>
                <div className="field">
                  <label htmlFor="company">{t('contact.label_company')}</label>
                  <input
                    type="text"
                    id="company"
                    value={data.company}
                    onChange={(e) => setData('company', e.target.value)}
                  />
                </div>
                <div className="field field--full">
                  <label htmlFor="subject">{t('contact.label_subject')}</label>
                  <select
                    id="subject"
                    required
                    value={data.subject}
                    onChange={(e) => setData('subject', e.target.value)}
                  >
                    <option value="" disabled>{t('contact.subject_placeholder')}</option>
                    {Object.entries(subjects).map(([slug]) => (
                      <option key={slug} value={slug}>{t(`contact.${slug}`)}</option>
                    ))}
                  </select>
                </div>
                <div className="field field--full">
                  <label htmlFor="message">{t('contact.label_message')}</label>
                  <textarea
                    id="message"
                    rows={6}
                    required
                    placeholder={t('contact.message_placeholder')}
                    value={data.message}
                    onChange={(e) => setData('message', e.target.value)}
                  />
                </div>
              </div>

              <label className="consent-check">
                <input type="checkbox" required checked={data.consent} onChange={(e) => setData('consent', e.target.checked)} />
                <span>
                  {t('contact.consent_text')} <Link href="/confidentialite">{t('contact.consent_link')}</Link>.
                </span>
              </label>

              {turnstileSiteKey && (
                <div className="contact-turnstile-wrap">
                  <div className="cf-turnstile" data-sitekey={turnstileSiteKey} />
                </div>
              )}

              <button type="submit" disabled={processing} className="btn btn--signal">
                {t('contact.submit')} <span className="btn__arrow">→</span>
              </button>
            </form>
          </div>

          <div ref={info.ref} className={`contact-info ${info.className}`} style={info.style}>
            <div className="contact-info__item">
              <span className="contact-info__icon">@</span>
              <div>
                <h4>{t('contact.info_email_label')}</h4>
                <a href="mailto:contact@slapia.com">contact@slapia.com</a>
              </div>
            </div>
            <div className="contact-info__item">
              <span className="contact-info__icon">◎</span>
              <div>
                <h4>{t('contact.info_zone_label')}</h4>
                <p>{t('contact.info_zone_text')}</p>
              </div>
            </div>
            <div className="contact-info__item">
              <span className="contact-info__icon">✓</span>
              <div>
                <h4>{t('contact.info_delay_label')}</h4>
                <p>{t('contact.info_delay_text')}</p>
              </div>
            </div>
          </div>
        </div>
      </section>
    </>
  );
}
