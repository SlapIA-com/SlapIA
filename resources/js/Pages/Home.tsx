import { Head, Link } from '@inertiajs/react';
import { useTranslation } from '../hooks/useTranslation';
import { Container, Eyebrow, SectionHead, ValueCard, StatNumber, Tag } from '../Components/ui';
import Reveal from '../Components/Reveal';
import type { Level, PublicReview, Stats } from '../types';

export default function Home({ stats, reviews }: { stats: Stats; reviews: PublicReview[] }) {
  const { t } = useTranslation();
  const levels: Level[] = t('levels');

  return (
    <>
      <Head title={t('home.meta_title')}>
        <meta name="description" content={t('home.meta_description')} />
      </Head>

      {/* Hero */}
      <section className="relative overflow-hidden border-b border-line">
        <Container className="grid gap-12 py-20 lg:grid-cols-2 lg:items-center lg:py-28">
          <div>
            <Eyebrow>{t('home.hero_eyebrow')}</Eyebrow>
            <h1 className="mt-4 font-display text-4xl font-bold leading-[1.1] text-ink sm:text-5xl">
              {t('home.hero_title_line1')}
              <br />
              <mark className="rounded bg-signal/20 px-1 text-signal-deep dark:text-signal">{t('home.hero_title_mark')}</mark>
            </h1>
            <p className="mt-6 max-w-lg text-base text-ink-fade">{t('home.hero_lede')}</p>
            <div className="mt-8 flex flex-wrap gap-4">
              <Link href="/formations" className="inline-flex items-center gap-2 rounded-full bg-ink px-6 py-3 text-sm font-semibold text-white hover:bg-ink-soft">
                {t('home.cta_primary')} <span>→</span>
              </Link>
              <Link href="/contact" className="inline-flex items-center gap-2 rounded-full border border-line-strong px-6 py-3 text-sm font-semibold text-ink hover:border-signal">
                {t('home.cta_secondary')}
              </Link>
            </div>
            <div className="mt-12 grid grid-cols-3 gap-6 border-t border-line pt-8">
              <div>
                <StatNumber value={stats.is_live ? stats.entreprises : null} fallback={t('home.stat1_num')} suffix="+" />
                <div className="mt-1 text-xs text-ink-fade">{t('home.stat1_label')}</div>
              </div>
              <div>
                <StatNumber value={stats.is_live ? stats.particuliers : null} fallback={t('home.stat2_num')} suffix="+" />
                <div className="mt-1 text-xs text-ink-fade">{t('home.stat2_label')}</div>
              </div>
              <div>
                <StatNumber value={stats.is_live && stats.satisfaction !== null ? stats.satisfaction : null} decimals={1} fallback={t('home.stat3_num')} suffix="/5" />
                <div className="mt-1 text-xs text-ink-fade">{t('home.stat3_label')}</div>
              </div>
            </div>
          </div>

          <Reveal className="rounded-3xl border border-line bg-paper p-6 shadow-sm">
            <span className="text-xs font-semibold uppercase tracking-wide text-ink-fade">{t('home.panel_label')}</span>
            <div className="mt-4 space-y-3">
              {[1, 2, 3].map((n) => (
                <div key={n} className="flex items-center justify-between rounded-xl border border-line bg-mist px-4 py-3">
                  <Tag signal>{t(`home.panel${n}_tag`)}</Tag>
                  <div className="text-right">
                    <div className="text-sm font-semibold text-ink">{t(`home.panel${n}_title`)}</div>
                    <div className="text-xs text-ink-fade">{t(`home.panel${n}_meta`)}</div>
                  </div>
                </div>
              ))}
            </div>
          </Reveal>
        </Container>
      </section>

      {/* Problem */}
      <section className="bg-surface-dark py-20 text-on-dark">
        <Container>
          <SectionHead dark eyebrow={t('home.problem_eyebrow')} title={t('home.problem_title')} note={t('home.problem_note')} />
          <div className="grid gap-6 sm:grid-cols-3">
            {[1, 2, 3].map((n) => (
              <Reveal key={n} delay={n * 80}>
                <div className="rounded-2xl border border-white/15 p-6">
                  <div className="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-white/10">{n === 1 ? '⚠' : n === 2 ? '⏱' : '◎'}</div>
                  <h3 className="font-display text-lg font-semibold">{t(`home.problem_card${n}_title`)}</h3>
                  <p className="mt-2 text-sm text-on-dark/65">{t(`home.problem_card${n}_text`)}</p>
                </div>
              </Reveal>
            ))}
          </div>
        </Container>
      </section>

      {/* Catalogue */}
      <section className="py-20">
        <Container>
          <SectionHead eyebrow={t('home.catalogue_eyebrow')} title={t('home.catalogue_title')} note={t('home.catalogue_note')} />
          <div className="grid gap-6 sm:grid-cols-3">
            {levels.map((level, i) => (
              <Reveal key={level.anchor} delay={i * 80}>
                <Link href={`/formations#${level.anchor}`} className="flex h-full flex-col rounded-2xl border border-line bg-paper p-6 transition-shadow hover:shadow-lg">
                  <Tag signal>Niveau {level.num}</Tag>
                  <h3 className="mt-4 font-display text-lg font-semibold text-ink">{level.title}</h3>
                  <p className="mt-2 flex-1 text-sm text-ink-fade">{level.teaser}</p>
                  <div className="mt-4 flex flex-wrap gap-2">
                    {level.tools.map((tool) => <Tag key={tool}>{tool}</Tag>)}
                  </div>
                  <span className="mt-6 text-sm font-semibold text-signal-deep dark:text-signal">{t('home.catalogue_link')} →</span>
                </Link>
              </Reveal>
            ))}
          </div>
        </Container>
      </section>

      {/* Method */}
      <section className="bg-paper py-20">
        <Container>
          <SectionHead eyebrow={t('home.method_eyebrow')} title={t('home.method_title')} note={t('home.method_note')} />
          <div className="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
            {[1, 2, 3, 4].map((n) => (
              <Reveal key={n} delay={n * 60}>
                <span className="font-display text-3xl font-bold text-line-strong">{String(n).padStart(2, '0')}</span>
                <h3 className="mt-2 font-display text-base font-semibold text-ink">{t(`home.method${n}_title`)}</h3>
                <p className="mt-2 text-sm text-ink-fade">{t(`home.method${n}_text`)}</p>
              </Reveal>
            ))}
          </div>
        </Container>
      </section>

      {/* Testimonials */}
      <section className="py-20">
        <Container>
          <SectionHead eyebrow={t('home.testimonials_eyebrow')} title={t('home.testimonials_title')} note={reviews.length === 0 ? t('home.testimonials_note') : undefined} />
          {reviews.length > 0 ? (
            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
              {reviews.slice(0, 6).map((r, i) => (
                <Reveal key={i} delay={i * 60}>
                  <div className="flex h-full flex-col rounded-2xl border border-line bg-paper p-6">
                    <div className="flex items-center gap-3">
                      <div className="flex h-10 w-10 items-center justify-center rounded-full bg-signal/15 text-sm font-semibold text-signal-deep">
                        {(r.prenom[0] ?? '') + (r.nom[0] ?? '')}
                      </div>
                      <div>
                        <div className="text-sm font-semibold text-ink">{r.prenom} {r.nom}</div>
                        {r.profession && <div className="text-xs text-ink-fade">{r.profession}{r.entreprise ? ` · ${r.entreprise}` : ''}</div>}
                      </div>
                    </div>
                    <p className="mt-4 flex-1 text-sm text-ink-soft">« {r.avis} »</p>
                    {r.note !== null && <div className="mt-4 text-signal-deep">{'★'.repeat(Math.round(r.note))}{'☆'.repeat(5 - Math.round(r.note))}</div>}
                  </div>
                </Reveal>
              ))}
            </div>
          ) : (
            <div className="grid gap-6 sm:grid-cols-3">
              {[1, 2, 3].map((n) => (
                <div key={n} className="rounded-2xl border border-line bg-paper p-6">
                  <p className="text-sm text-ink-soft">« {t(`home.quote${n}_text`)} »</p>
                  <div className="mt-4 text-sm font-semibold text-ink">{t(`home.quote${n}_name`)}</div>
                  <div className="text-xs text-ink-fade">{t(`home.quote${n}_role`)}</div>
                </div>
              ))}
            </div>
          )}
        </Container>
      </section>

      {/* PC teaser */}
      <section className="bg-paper py-20">
        <Container>
          <SectionHead eyebrow={t('home.pc_teaser_eyebrow')} title={t('home.pc_teaser_title')} note={t('home.pc_teaser_note')} />
          <div className="grid gap-6 sm:grid-cols-3">
            {[1, 2, 3].map((n) => (
              <ValueCard key={n} title={t(`home.pc_card${n}_title`)} text={t(`home.pc_card${n}_text`)} />
            ))}
          </div>
          <Link href="/services-pc" className="mt-8 inline-flex items-center gap-2 text-sm font-semibold text-signal-deep dark:text-signal">
            {t('home.pc_teaser_link')} →
          </Link>
        </Container>
      </section>

      {/* Final CTA */}
      <section className="py-20">
        <Container>
          <div className="rounded-3xl bg-surface-dark p-10 text-center text-on-dark sm:p-16">
            <h2 className="font-display text-2xl font-semibold sm:text-3xl">{t('home.final_cta_title')}</h2>
            <div className="mt-6 flex flex-wrap justify-center gap-4">
              <Link href="/contact" className="rounded-full bg-signal px-6 py-3 text-sm font-semibold text-on-accent">{t('home.final_cta_primary')}</Link>
              <Link href="/tarifs" className="rounded-full border border-white/20 px-6 py-3 text-sm font-semibold text-on-dark">{t('home.final_cta_secondary')}</Link>
            </div>
          </div>
        </Container>
      </section>
    </>
  );
}
