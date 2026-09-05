import { Head, Link, useForm } from '@inertiajs/react';
import { useTranslation } from '../hooks/useTranslation';
import { Container, Eyebrow } from '../Components/ui';

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

  function submit(e: React.FormEvent) {
    e.preventDefault();
    post('/contact');
  }

  return (
    <>
      <Head title={t('contact.meta_title')}>
        <meta name="description" content={t('contact.meta_description')} />
      </Head>

      <section className="border-b border-line py-20">
        <Container>
          <Eyebrow>{t('contact.eyebrow')}</Eyebrow>
          <h1 className="mt-4 max-w-2xl font-display text-4xl font-bold text-ink sm:text-5xl">
            {t('contact.title_pre')}<mark className="rounded bg-signal/20 px-1 text-signal-deep dark:text-signal">{t('contact.title_mark')}</mark>{t('contact.title_post')}
          </h1>
          <p className="mt-6 max-w-xl text-ink-fade">{t('contact.lede')}</p>
        </Container>
      </section>

      <section className="py-16">
        <Container className="grid gap-10 lg:grid-cols-[1.4fr_1fr]">
          <div className="rounded-2xl border border-line bg-paper p-6 sm:p-8">
            {sent && (
              <div className="mb-6 rounded-xl bg-success/10 px-4 py-3 text-sm text-success">✓ {t('contact.success')}</div>
            )}
            {Object.keys(errors).length > 0 && (
              <div className="mb-6 rounded-xl bg-danger/10 px-4 py-3 text-sm text-danger">
                {Object.values(errors).join(' ')}
              </div>
            )}

            <form onSubmit={submit} className="space-y-5">
              <div className="grid gap-5 sm:grid-cols-2">
                <Field label={t('contact.label_firstname')}>
                  <input required value={data.firstname} onChange={(e) => setData('firstname', e.target.value)} className="input" />
                </Field>
                <Field label={t('contact.label_lastname')}>
                  <input value={data.lastname} onChange={(e) => setData('lastname', e.target.value)} className="input" />
                </Field>
                <Field label={t('contact.label_email')}>
                  <input required type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} className="input" />
                </Field>
                <Field label={t('contact.label_company')}>
                  <input value={data.company} onChange={(e) => setData('company', e.target.value)} className="input" />
                </Field>
                <Field label={t('contact.label_subject')} full>
                  <select required value={data.subject} onChange={(e) => setData('subject', e.target.value)} className="input">
                    <option value="" disabled>{t('contact.subject_placeholder')}</option>
                    {Object.entries(subjects).map(([slug]) => (
                      <option key={slug} value={slug}>{t(`contact.${slug}`)}</option>
                    ))}
                  </select>
                </Field>
                <Field label={t('contact.label_message')} full>
                  <textarea required rows={6} placeholder={t('contact.message_placeholder')} value={data.message} onChange={(e) => setData('message', e.target.value)} className="input" />
                </Field>
              </div>

              <label className="flex items-start gap-2 text-sm text-ink-fade">
                <input type="checkbox" required checked={data.consent} onChange={(e) => setData('consent', e.target.checked)} className="mt-1" />
                <span>{t('contact.consent_text')} <Link href="/confidentialite" className="underline">{t('contact.consent_link')}</Link>.</span>
              </label>

              {turnstileSiteKey && <div className="cf-turnstile" data-sitekey={turnstileSiteKey} />}

              <button type="submit" disabled={processing} className="rounded-full bg-signal px-6 py-3 text-sm font-semibold text-on-accent disabled:opacity-60">
                {t('contact.submit')} →
              </button>
            </form>
          </div>

          <div className="space-y-6">
            <InfoItem icon="@" label={t('contact.info_email_label')}>
              <a href="mailto:contact@slapia.com" className="text-signal-deep dark:text-signal">contact@slapia.com</a>
            </InfoItem>
            <InfoItem icon="◎" label={t('contact.info_zone_label')}>{t('contact.info_zone_text')}</InfoItem>
            <InfoItem icon="✓" label={t('contact.info_delay_label')}>{t('contact.info_delay_text')}</InfoItem>
          </div>
        </Container>
      </section>

      <style>{`.input { width:100%; border:1px solid rgb(var(--c-line)); border-radius:0.75rem; padding:0.65rem 0.9rem; background:rgb(var(--c-paper)); color:rgb(var(--c-ink)); font-size:0.9rem; } .input:focus { outline:2px solid rgb(var(--c-signal)); outline-offset:1px; }`}</style>
    </>
  );
}

function Field({ label, children, full = false }: { label: string; children: React.ReactNode; full?: boolean }) {
  return (
    <div className={full ? 'sm:col-span-2' : ''}>
      <label className="mb-1.5 block text-sm font-medium text-ink-soft">{label}</label>
      {children}
    </div>
  );
}

function InfoItem({ icon, label, children }: { icon: string; label: string; children: React.ReactNode }) {
  return (
    <div className="flex gap-4">
      <span className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-signal/10 text-signal-deep dark:text-signal">{icon}</span>
      <div>
        <h4 className="font-display text-sm font-semibold text-ink">{label}</h4>
        <div className="mt-1 text-sm text-ink-fade">{children}</div>
      </div>
    </div>
  );
}
