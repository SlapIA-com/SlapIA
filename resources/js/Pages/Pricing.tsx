import { Head, Link } from '@inertiajs/react';
import { useTranslation } from '../hooks/useTranslation';
import { useReveal } from '../hooks/useReveal';

/** Port fidèle de pages/tarifs.php (mêmes classes CSS legacy/style.css). */
export default function Pricing() {
  const { t } = useTranslation();
  const ctaBand = useReveal();

  return (
    <>
      <Head title={t('pricing.meta_title')}>
        <meta name="description" content={t('pricing.meta_description')} />
      </Head>

      <section className="page-hero">
        <div className="page-hero-canvas" aria-hidden="true">
          <span></span>
          <span></span>
          <span></span>
          <span></span>
          <span></span>
          <span></span>
        </div>
        <div className="container">
          <span className="eyebrow">{t('pricing.eyebrow')}</span>
          <h1 className="page-hero__title">
            {t('pricing.title_pre')}
            <mark>{t('pricing.title_mark')}</mark>
            {t('pricing.title_post')}
          </h1>
          <p className="page-hero__lede">{t('pricing.lede')}</p>
        </div>
      </section>

      <section className="section" style={{ paddingTop: 0 }} id="formations">
        <div className="container">
          <div className="section-head">
            <div>
              <span className="eyebrow">{t('pricing.formations_eyebrow')}</span>
              <h2 className="section-head__title">{t('pricing.formations_title')}</h2>
            </div>
            <p className="section-head__note">{t('courses_page.vip_lede')}</p>
          </div>

          <div className="grid-4" style={{ marginBottom: 40 }}>
            {[1, 2, 3, 4].map((n) => (
              <VipCard key={n} title={t(`courses_page.vip_f${n}_title`)} text={t(`courses_page.vip_f${n}_text`)} />
            ))}
          </div>

          <div className="pricing-grid pricing-grid--pair" id="mentorat">
            <FeaturedPriceCard
              kicker={t('pricing.tier3_kicker')}
              name={t('pricing.tier3_name')}
              desc={t('pricing.tier3_desc')}
              price={t('pricing.tier3_price')}
              unit={t('pricing.tier3_unit')}
              features={[t('pricing.tier3_f1'), t('pricing.tier3_f2'), t('pricing.tier3_f3'), t('pricing.tier3_f4')]}
              cta={t('pricing.tier3_cta')}
            />
            <FeaturedPriceCard
              kicker={t('courses_page.vip_kicker')}
              name={t('courses_page.vip_price_name')}
              desc={t('courses_page.vip_subtitle')}
              price={t('courses_page.vip_price')}
              unit={t('courses_page.vip_unit')}
              cta={t('courses_page.vip_cta')}
            />
          </div>
        </div>
      </section>

      <section className="section section--paper" id="pc">
        <div className="container">
          <div className="section-head">
            <div>
              <span className="eyebrow">{t('pricing.pc_eyebrow')}</span>
              <h2 className="section-head__title">{t('pricing.pc_title')}</h2>
            </div>
            <p className="section-head__note">{t('pricing.pc_note')}</p>
          </div>
          <div className="pricing-grid">
            <PlainPriceCard
              name={t('pricing.pctier1_name')}
              desc={t('pricing.pctier1_desc')}
              price={t('pricing.pctier1_price')}
              unit={t('pricing.pctier1_unit')}
              features={[t('pricing.pctier1_f1'), t('pricing.pctier1_f2'), t('pricing.pctier1_f3'), t('pricing.pctier1_f4')]}
              cta={t('pricing.pctier1_cta')}
              ctaVariant="ghost"
            />
            <PlainPriceCard
              featured
              badge={t('pricing.pctier2_badge')}
              name={t('pricing.pctier2_name')}
              desc={t('pricing.pctier2_desc')}
              price={t('pricing.pctier2_price')}
              unit={t('pricing.pctier2_unit')}
              features={[t('pricing.pctier2_f1'), t('pricing.pctier2_f2'), t('pricing.pctier2_f3'), t('pricing.pctier2_f4')]}
              cta={t('pricing.pctier2_cta')}
              ctaVariant="signal"
            />
            <PlainPriceCard
              name={t('pricing.pctier3_name')}
              desc={t('pricing.pctier3_desc')}
              price={t('pricing.pctier3_price')}
              unit={t('pricing.pctier3_unit')}
              features={[t('pricing.pctier3_f1'), t('pricing.pctier3_f2'), t('pricing.pctier3_f3'), t('pricing.pctier3_f4')]}
              cta={t('pricing.pctier3_cta')}
              ctaVariant="ghost"
            />
          </div>
        </div>
      </section>

      <section className="section">
        <div className="container">
          <div className="section-head">
            <div>
              <span className="eyebrow">{t('pricing.faq_eyebrow')}</span>
              <h2 className="section-head__title">{t('pricing.faq_title')}</h2>
            </div>
          </div>

          <div>
            {[1, 2, 3, 4, 5].map((n) => (
              <details className="faq-item" key={n} open={n === 1}>
                <summary>{t(`pricing.faq${n}_q`)}</summary>
                <div className="faq-item__body">{t(`pricing.faq${n}_a`)}</div>
              </details>
            ))}
          </div>
        </div>
      </section>

      <section className="section">
        <div className="container">
          <div ref={ctaBand.ref} className={`cta-band ${ctaBand.className}`}>
            <h2>{t('pricing.cta_title')}</h2>
            <div className="cta-band__actions">
              <Link href="/contact" className="btn btn--signal">
                {t('pricing.cta_btn')} <span className="btn__arrow">→</span>
              </Link>
            </div>
          </div>
        </div>
      </section>
    </>
  );
}

function VipCard({ title, text }: { title: string; text: string }) {
  const reveal = useReveal();
  return (
    <div ref={reveal.ref} className={`value-card ${reveal.className}`} style={reveal.style}>
      <div className="value-card__icon">◆</div>
      <h3>{title}</h3>
      <p>{text}</p>
    </div>
  );
}

function FeaturedPriceCard({
  kicker,
  name,
  desc,
  price,
  unit,
  features,
  cta,
}: {
  kicker: string;
  name: string;
  desc: string;
  price: string;
  unit: string;
  features?: string[];
  cta: string;
}) {
  const reveal = useReveal();
  return (
    <div ref={reveal.ref} className={`price-card price-card--featured ${reveal.className}`} style={reveal.style}>
      <div className="price-card__kicker">{kicker}</div>
      <div className="price-card__name">{name}</div>
      <div className="price-card__desc">{desc}</div>
      <div className="price-card__price">{price}</div>
      <div className="price-card__unit">{unit}</div>
      <hr className="divider" />
      {features && (
        <ul className="price-card__features">
          {features.map((f) => (
            <li key={f}>{f}</li>
          ))}
        </ul>
      )}
      <Link href="/contact" className="btn btn--signal btn--block">
        {cta}
      </Link>
    </div>
  );
}

function PlainPriceCard({
  featured = false,
  badge,
  name,
  desc,
  price,
  unit,
  features,
  cta,
  ctaVariant,
}: {
  featured?: boolean;
  badge?: string;
  name: string;
  desc: string;
  price: string;
  unit: string;
  features: string[];
  cta: string;
  ctaVariant: 'signal' | 'ghost';
}) {
  const reveal = useReveal();
  return (
    <div
      ref={reveal.ref}
      className={`price-card ${featured ? 'price-card--featured ' : ''}${reveal.className}`}
      style={reveal.style}
    >
      {badge && <span className="price-card__badge">{badge}</span>}
      <div className="price-card__name">{name}</div>
      <div className="price-card__desc">{desc}</div>
      <div className="price-card__price">{price}</div>
      <div className="price-card__unit">{unit}</div>
      <hr className="divider" />
      <ul className="price-card__features">
        {features.map((f) => (
          <li key={f}>{f}</li>
        ))}
      </ul>
      <Link href="/contact" className={`btn btn--${ctaVariant} btn--block`}>
        {cta}
      </Link>
    </div>
  );
}
