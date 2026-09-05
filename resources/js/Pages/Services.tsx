import { Head, Link } from '@inertiajs/react';
import { useTranslation } from '../hooks/useTranslation';
import { Container, Eyebrow, SectionHead, ValueCard, Tag } from '../Components/ui';
import Reveal from '../Components/Reveal';

export default function Services() {
  const { t } = useTranslation();

  return (
    <>
      <Head title={t('services.meta_title')}>
        <meta name="description" content={t('services.meta_description')} />
      </Head>

      <section className="border-b border-line py-20">
        <Container>
          <Eyebrow>{t('services.eyebrow')}</Eyebrow>
          <h1 className="mt-4 max-w-2xl font-display text-4xl font-bold text-ink sm:text-5xl">
            {t('services.title_pre')} <mark className="rounded bg-signal/20 px-1 text-signal-deep dark:text-signal">{t('services.title_mark')}</mark>
          </h1>
          <p className="mt-6 max-w-xl text-ink-fade">{t('services.lede')}</p>
        </Container>
      </section>

      <section className="py-16">
        <Container>
          <div className="grid gap-6 sm:grid-cols-2">
            <ValueCard title={t('services.audience1_title')} text={t('services.audience1_text')} />
            <ValueCard title={t('services.audience2_title')} text={t('services.audience2_text')} />
          </div>
        </Container>
      </section>

      <section className="bg-paper py-16">
        <Container>
          <SectionHead eyebrow={t('services.services_eyebrow')} title={t('services.services_title')} note={t('services.services_note')} />
          <div className="grid gap-6 sm:grid-cols-3">
            {[
              { id: 'montage', n: 1 },
              { id: 'devis', n: 2 },
              { id: 'diagnostic', n: 3 },
            ].map(({ id, n }) => (
              <div key={id} id={id} className="scroll-mt-24 flex flex-col rounded-2xl border border-line bg-paper p-6">
                <div className="flex flex-wrap gap-2">
                  <Tag signal>{t(`services.card${n}_tag`)}</Tag>
                  <Tag>{t(`services.card${n}_tag2`)}</Tag>
                </div>
                <h3 className="mt-4 font-display text-lg font-semibold text-ink">{t(`services.card${n}_title`)}</h3>
                <p className="mt-2 flex-1 text-sm text-ink-fade">{t(`services.card${n}_desc`)}</p>
                <div className="mt-6 flex items-center justify-between border-t border-line pt-4">
                  <span className="text-sm font-semibold text-ink">{t(`services.card${n}_price`)}</span>
                  <Link href="/contact" className="text-sm font-semibold text-signal-deep dark:text-signal">{t(`services.card${n}_cta`)} →</Link>
                </div>
              </div>
            ))}
          </div>
        </Container>
      </section>

      <section className="py-16">
        <Container>
          <SectionHead eyebrow={t('services.method_eyebrow')} title={t('services.method_title')} note={t('services.method_note')} />
          <div className="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
            {[1, 2, 3, 4].map((n) => (
              <Reveal key={n} delay={n * 60}>
                <span className="font-display text-3xl font-bold text-line-strong">{String(n).padStart(2, '0')}</span>
                <h3 className="mt-2 font-display text-base font-semibold text-ink">{t(`services.method${n}_title`)}</h3>
                <p className="mt-2 text-sm text-ink-fade">{t(`services.method${n}_text`)}</p>
              </Reveal>
            ))}
          </div>
        </Container>
      </section>

      <section className="bg-paper py-20">
        <Container>
          <div className="rounded-3xl bg-surface-dark p-10 text-center text-on-dark sm:p-16">
            <h2 className="font-display text-2xl font-semibold sm:text-3xl">{t('services.cta_title')}</h2>
            <div className="mt-6 flex flex-wrap justify-center gap-4">
              <Link href="/contact" className="rounded-full bg-signal px-6 py-3 text-sm font-semibold text-on-accent">{t('services.cta_primary')}</Link>
              <Link href="/tarifs#pc" className="rounded-full border border-white/20 px-6 py-3 text-sm font-semibold text-on-dark">{t('services.cta_secondary')}</Link>
            </div>
          </div>
        </Container>
      </section>
    </>
  );
}
