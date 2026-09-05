import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from '../hooks/useTranslation';
import { Container, Eyebrow, SectionHead, ValueCard } from '../Components/ui';

function PriceCard({
  featured = false,
  badge,
  kicker,
  name,
  desc,
  price,
  unit,
  features,
  cta,
}: {
  featured?: boolean;
  badge?: string;
  kicker?: string;
  name: string;
  desc: string;
  price: string;
  unit: string;
  features?: string[];
  cta: string;
}) {
  return (
    <div className={`relative flex flex-col rounded-2xl border p-6 ${featured ? 'border-signal bg-paper shadow-lg' : 'border-line bg-paper'}`}>
      {badge && <span className="absolute -top-3 left-6 rounded-full bg-signal px-3 py-1 text-xs font-semibold text-on-accent">{badge}</span>}
      {kicker && <span className="text-xs font-semibold uppercase tracking-wide text-ink-fade">{kicker}</span>}
      <h3 className="mt-1 font-display text-lg font-semibold text-ink">{name}</h3>
      <p className="mt-1 text-sm text-ink-fade">{desc}</p>
      <div className="mt-6 font-display text-3xl font-bold text-ink">{price}</div>
      <div className="text-xs text-ink-fade">{unit}</div>
      {features && (
        <>
          <hr className="my-6 border-line" />
          <ul className="flex-1 space-y-2 text-sm text-ink-soft">
            {features.map((f) => (
              <li key={f} className="flex gap-2"><span className="text-signal-deep dark:text-signal">✓</span>{f}</li>
            ))}
          </ul>
        </>
      )}
      <Link
        href="/contact"
        className={`mt-6 inline-flex items-center justify-center rounded-full px-5 py-3 text-sm font-semibold ${
          featured ? 'bg-signal text-on-accent' : 'border border-line-strong text-ink'
        }`}
      >
        {cta}
      </Link>
    </div>
  );
}

function FaqItem({ q, a, defaultOpen = false }: { q: string; a: string; defaultOpen?: boolean }) {
  const [open, setOpen] = useState(defaultOpen);
  return (
    <div className="border-b border-line py-4">
      <button className="flex w-full items-center justify-between text-left font-medium text-ink" onClick={() => setOpen(!open)}>
        {q}
        <span className="text-ink-fade">{open ? '−' : '+'}</span>
      </button>
      {open && <div className="mt-3 text-sm text-ink-fade">{a}</div>}
    </div>
  );
}

export default function Pricing() {
  const { t } = useTranslation();

  return (
    <>
      <Head title={t('pricing.meta_title')}>
        <meta name="description" content={t('pricing.meta_description')} />
      </Head>

      <section className="border-b border-line py-20">
        <Container>
          <Eyebrow>{t('pricing.eyebrow')}</Eyebrow>
          <h1 className="mt-4 max-w-2xl font-display text-4xl font-bold text-ink sm:text-5xl">
            {t('pricing.title_pre')}<mark className="rounded bg-signal/20 px-1 text-signal-deep dark:text-signal">{t('pricing.title_mark')}</mark>{t('pricing.title_post')}
          </h1>
          <p className="mt-6 max-w-xl text-ink-fade">{t('pricing.lede')}</p>
        </Container>
      </section>

      <section id="formations" className="scroll-mt-24 py-16">
        <Container>
          <SectionHead eyebrow={t('pricing.formations_eyebrow')} title={t('pricing.formations_title')} note={t('courses_page.vip_lede')} />
          <div className="mb-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            {[1, 2, 3, 4].map((n) => (
              <ValueCard key={n} title={t(`courses_page.vip_f${n}_title`)} text={t(`courses_page.vip_f${n}_text`)} />
            ))}
          </div>
          <div id="mentorat" className="scroll-mt-24 grid gap-6 sm:grid-cols-2">
            <PriceCard
              featured
              kicker={t('pricing.tier3_kicker')}
              name={t('pricing.tier3_name')}
              desc={t('pricing.tier3_desc')}
              price={t('pricing.tier3_price')}
              unit={t('pricing.tier3_unit')}
              features={[t('pricing.tier3_f1'), t('pricing.tier3_f2'), t('pricing.tier3_f3'), t('pricing.tier3_f4')]}
              cta={t('pricing.tier3_cta')}
            />
            <PriceCard
              featured
              kicker={t('courses_page.vip_kicker')}
              name={t('courses_page.vip_price_name')}
              desc={t('courses_page.vip_subtitle')}
              price={t('courses_page.vip_price')}
              unit={t('courses_page.vip_unit')}
              cta={t('courses_page.vip_cta')}
            />
          </div>
        </Container>
      </section>

      <section id="pc" className="scroll-mt-24 bg-paper py-16">
        <Container>
          <SectionHead eyebrow={t('pricing.pc_eyebrow')} title={t('pricing.pc_title')} note={t('pricing.pc_note')} />
          <div className="grid gap-6 sm:grid-cols-3">
            <PriceCard
              name={t('pricing.pctier1_name')}
              desc={t('pricing.pctier1_desc')}
              price={t('pricing.pctier1_price')}
              unit={t('pricing.pctier1_unit')}
              features={[t('pricing.pctier1_f1'), t('pricing.pctier1_f2'), t('pricing.pctier1_f3'), t('pricing.pctier1_f4')]}
              cta={t('pricing.pctier1_cta')}
            />
            <PriceCard
              featured
              badge={t('pricing.pctier2_badge')}
              name={t('pricing.pctier2_name')}
              desc={t('pricing.pctier2_desc')}
              price={t('pricing.pctier2_price')}
              unit={t('pricing.pctier2_unit')}
              features={[t('pricing.pctier2_f1'), t('pricing.pctier2_f2'), t('pricing.pctier2_f3'), t('pricing.pctier2_f4')]}
              cta={t('pricing.pctier2_cta')}
            />
            <PriceCard
              name={t('pricing.pctier3_name')}
              desc={t('pricing.pctier3_desc')}
              price={t('pricing.pctier3_price')}
              unit={t('pricing.pctier3_unit')}
              features={[t('pricing.pctier3_f1'), t('pricing.pctier3_f2'), t('pricing.pctier3_f3'), t('pricing.pctier3_f4')]}
              cta={t('pricing.pctier3_cta')}
            />
          </div>
        </Container>
      </section>

      <section className="py-16">
        <Container className="max-w-3xl">
          <SectionHead eyebrow={t('pricing.faq_eyebrow')} title={t('pricing.faq_title')} />
          {[1, 2, 3, 4, 5].map((n) => (
            <FaqItem key={n} defaultOpen={n === 1} q={t(`pricing.faq${n}_q`)} a={t(`pricing.faq${n}_a`)} />
          ))}
        </Container>
      </section>

      <section className="pb-20">
        <Container>
          <div className="rounded-3xl bg-surface-dark p-10 text-center text-on-dark sm:p-16">
            <h2 className="font-display text-2xl font-semibold sm:text-3xl">{t('pricing.cta_title')}</h2>
            <Link href="/contact" className="mt-6 inline-flex rounded-full bg-signal px-6 py-3 text-sm font-semibold text-on-accent">{t('pricing.cta_btn')} →</Link>
          </div>
        </Container>
      </section>
    </>
  );
}
