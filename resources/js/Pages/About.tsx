import { Head, Link } from '@inertiajs/react';
import { useTranslation } from '../hooks/useTranslation';
import { Container, Eyebrow, SectionHead, ValueCard, StatNumber } from '../Components/ui';
import Reveal from '../Components/Reveal';
import type { Level, Stats } from '../types';

export default function About({ stats }: { stats: Stats }) {
  const { t } = useTranslation();
  const levels: Level[] = t('levels');

  return (
    <>
      <Head title={t('about.meta_title')}>
        <meta name="description" content={t('about.meta_description')} />
      </Head>

      <section className="border-b border-line py-20">
        <Container>
          <Eyebrow>{t('about.eyebrow')}</Eyebrow>
          <h1 className="mt-4 max-w-2xl font-display text-4xl font-bold text-ink sm:text-5xl">
            {t('about.title_pre')}<mark className="rounded bg-signal/20 px-1 text-signal-deep dark:text-signal">{t('about.title_mark')}</mark>{t('about.title_post')}
          </h1>
          <p className="mt-6 max-w-xl text-ink-fade">{t('about.lede')}</p>
        </Container>
      </section>

      <section className="py-14">
        <Container>
          <div className="grid grid-cols-2 gap-6 sm:grid-cols-4">
            <div><StatNumber value={stats.is_live ? stats.entreprises : null} suffix="+" fallback={t('about.stat1_num')} /><div className="mt-1 text-xs text-ink-fade">{t('about.stat1_label')}</div></div>
            <div><StatNumber value={stats.is_live ? stats.particuliers : null} suffix="+" fallback={t('about.stat2_num')} /><div className="mt-1 text-xs text-ink-fade">{t('about.stat2_label')}</div></div>
            <div><StatNumber value={levels.length} fallback={t('about.stat3_num')} /><div className="mt-1 text-xs text-ink-fade">{t('about.stat3_label')}</div></div>
            <div><StatNumber value={stats.is_live && stats.satisfaction !== null ? stats.satisfaction : null} decimals={1} suffix="/5" fallback={t('about.stat4_num')} /><div className="mt-1 text-xs text-ink-fade">{t('about.stat4_label')}</div></div>
          </div>
        </Container>
      </section>

      <section className="bg-paper py-16">
        <Container>
          <SectionHead eyebrow={t('about.philosophy_eyebrow')} title={t('about.philosophy_title')} />
          <div className="grid gap-6 sm:grid-cols-3">
            {[1, 2, 3].map((n) => (
              <ValueCard key={n} title={t(`about.value${n}_title`)} text={t(`about.value${n}_text`)} />
            ))}
          </div>
        </Container>
      </section>

      <section className="py-16">
        <Container>
          <SectionHead eyebrow={t('about.team_eyebrow')} title={t('about.team_title')} />
          <div className="flex flex-col gap-6 rounded-2xl border border-line bg-paper p-6 sm:flex-row sm:items-center">
            <img src="/assets/img/team/Thomas-Lapierre.jpg" alt={t('about.founder_name')} className="h-28 w-28 rounded-2xl object-cover" />
            <div>
              <h3 className="font-display text-xl font-semibold text-ink">{t('about.founder_name')}</h3>
              <span className="text-sm font-medium text-signal-deep dark:text-signal">{t('about.founder_role')}</span>
              <p className="mt-3 text-sm text-ink-fade">{t('about.founder_bio')}</p>
            </div>
          </div>

          <div className="mt-8 grid gap-4 sm:grid-cols-2">
            {[1, 2].map((n) => (
              <a
                key={n}
                href={`/assets/img/certifications/Formation_iA_Niveau_${n}_Entreprise.jpg`}
                target="_blank"
                rel="noopener"
                className="flex items-center gap-4 rounded-2xl border border-line bg-paper p-4 hover:shadow"
              >
                <img src={`/assets/img/certifications/Formation_iA_Niveau_${n}_Entreprise.jpg`} alt={t(`about.cert${n}_title`)} className="h-16 w-16 rounded-lg object-cover" />
                <div>
                  <div className="text-sm font-semibold text-ink">{t(`about.cert${n}_title`)}</div>
                  <div className="text-xs text-ink-fade">{t(`about.cert${n}_meta`)}</div>
                </div>
              </a>
            ))}
          </div>
        </Container>
      </section>

      <section className="bg-paper py-16">
        <Container>
          <SectionHead eyebrow={t('about.timeline_eyebrow')} title={t('about.timeline_title')} />
          <div className="space-y-6 border-l border-line pl-6">
            {[1, 2, 3, 4].map((n) => (
              <Reveal key={n} delay={n * 60}>
                <time className="text-xs font-semibold uppercase tracking-wide text-signal-deep dark:text-signal">{t(`about.tl${n}_year`)}</time>
                <h4 className="mt-1 font-display text-base font-semibold text-ink">{t(`about.tl${n}_title`)}</h4>
                <p className="mt-1 text-sm text-ink-fade">{t(`about.tl${n}_text`)}</p>
              </Reveal>
            ))}
          </div>
        </Container>
      </section>

      <section className="py-20">
        <Container>
          <div className="rounded-3xl bg-surface-dark p-10 text-center text-on-dark sm:p-16">
            <h2 className="font-display text-2xl font-semibold sm:text-3xl">{t('about.cta_title')}</h2>
            <Link href="/contact" className="mt-6 inline-flex rounded-full bg-signal px-6 py-3 text-sm font-semibold text-on-accent">{t('about.cta_btn')} →</Link>
          </div>
        </Container>
      </section>
    </>
  );
}
