import { Head, Link } from '@inertiajs/react';
import { useTranslation } from '../hooks/useTranslation';
import { useReveal } from '../hooks/useReveal';

/** Port fidèle de pages/services-pc.php (mêmes classes CSS legacy/style.css). */
export default function Services() {
  const { t } = useTranslation();
  const ctaBand = useReveal();

  return (
    <>
      <Head title={t('services.meta_title')}>
        <meta name="description" content={t('services.meta_description')} />
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
          <span className="eyebrow">{t('services.eyebrow')}</span>
          <h1 className="page-hero__title">
            {t('services.title_pre')} <mark>{t('services.title_mark')}</mark>
          </h1>
          <p className="page-hero__lede">{t('services.lede')}</p>
        </div>
      </section>

      <section className="section" style={{ paddingTop: 0 }}>
        <div className="container">
          <div className="grid-2">
            <AudienceCard title={t('services.audience1_title')} text={t('services.audience1_text')} />
            <AudienceCard title={t('services.audience2_title')} text={t('services.audience2_text')} />
          </div>
        </div>
      </section>

      <section className="section section--paper">
        <div className="container">
          <div className="section-head">
            <div>
              <span className="eyebrow">{t('services.services_eyebrow')}</span>
              <h2 className="section-head__title">{t('services.services_title')}</h2>
            </div>
            <p className="section-head__note">{t('services.services_note')}</p>
          </div>

          <div className="grid-3">
            <ServiceCard
              id="montage"
              tag={t('services.card1_tag')}
              tag2={t('services.card1_tag2')}
              title={t('services.card1_title')}
              desc={t('services.card1_desc')}
              price={t('services.card1_price')}
              cta={t('services.card1_cta')}
            />
            <ServiceCard
              id="devis"
              tag={t('services.card2_tag')}
              tag2={t('services.card2_tag2')}
              title={t('services.card2_title')}
              desc={t('services.card2_desc')}
              price={t('services.card2_price')}
              cta={t('services.card2_cta')}
            />
            <ServiceCard
              id="diagnostic"
              tag={t('services.card3_tag')}
              tag2={t('services.card3_tag2')}
              title={t('services.card3_title')}
              desc={t('services.card3_desc')}
              price={t('services.card3_price')}
              cta={t('services.card3_cta')}
            />
          </div>
        </div>
      </section>

      <section className="section">
        <div className="container">
          <div className="section-head">
            <div>
              <span className="eyebrow">{t('services.method_eyebrow')}</span>
              <h2 className="section-head__title">{t('services.method_title')}</h2>
            </div>
            <p className="section-head__note">{t('services.method_note')}</p>
          </div>
          <div className="method-list">
            {[1, 2, 3, 4].map((n) => (
              <MethodItem
                key={n}
                num={String(n).padStart(2, '0')}
                title={t(`services.method${n}_title`)}
                text={t(`services.method${n}_text`)}
              />
            ))}
          </div>
        </div>
      </section>

      <section className="section section--paper">
        <div className="container">
          <div ref={ctaBand.ref} className={`cta-band ${ctaBand.className}`}>
            <h2>{t('services.cta_title')}</h2>
            <div className="cta-band__actions">
              <Link href="/contact" className="btn btn--signal">
                {t('services.cta_primary')} <span className="btn__arrow">→</span>
              </Link>
              <Link href="/tarifs#pc" className="btn btn--on-dark">
                {t('services.cta_secondary')}
              </Link>
            </div>
          </div>
        </div>
      </section>
    </>
  );
}

function AudienceCard({ title, text }: { title: string; text: string }) {
  const reveal = useReveal();
  return (
    <div ref={reveal.ref} className={`value-card ${reveal.className}`} style={reveal.style}>
      <div className="value-card__icon">◆</div>
      <h3>{title}</h3>
      <p>{text}</p>
    </div>
  );
}

function ServiceCard({
  id,
  tag,
  tag2,
  title,
  desc,
  price,
  cta,
}: {
  id: string;
  tag: string;
  tag2: string;
  title: string;
  desc: string;
  price: string;
  cta: string;
}) {
  const reveal = useReveal();
  return (
    <div ref={reveal.ref} className={`course-card ${reveal.className}`} style={reveal.style} id={id}>
      <div className="course-card__meta">
        <span className="tag tag--signal">{tag}</span>
        <span className="tag tag--ghost">{tag2}</span>
      </div>
      <h3>{title}</h3>
      <p className="desc">{desc}</p>
      <div className="course-card__foot">
        <span className="course-card__price">{price}</span>
        <Link href="/contact" className="course-card__link">
          {cta} <span className="btn__arrow">→</span>
        </Link>
      </div>
    </div>
  );
}

function MethodItem({ num, title, text }: { num: string; title: string; text: string }) {
  const reveal = useReveal();
  return (
    <div ref={reveal.ref} className={`method-item ${reveal.className}`} style={reveal.style}>
      <span className="method-item__num">{num}</span>
      <div>
        <h3>{title}</h3>
        <p>{text}</p>
      </div>
    </div>
  );
}
