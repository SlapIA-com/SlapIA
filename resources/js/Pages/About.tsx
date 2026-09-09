import { Head, Link } from '@inertiajs/react';
import { useTranslation } from '../hooks/useTranslation';
import { useReveal } from '../hooks/useReveal';
import LegacyStatNumber from '../Components/LegacyStatNumber';
import type { Level, Stats } from '../types';

/** Port fidèle de pages/a-propos.php (mêmes classes CSS legacy/style.css). */
export default function About({ stats }: { stats: Stats }) {
  const { t, locale } = useTranslation();
  const levels: Level[] = t('levels');
  const decimalSep = locale === 'en' ? '.' : ',';

  return (
    <>
      <Head title={t('about.meta_title')}>
        <meta name="description" content={t('about.meta_description')} />
      </Head>

      <section className="page-hero">
        <div className="page-hero-canvas" aria-hidden="true">
          <span></span><span></span><span></span><span></span><span></span><span></span>
        </div>
        <div className="container">
          <span className="eyebrow">{t('about.eyebrow')}</span>
          <h1 className="page-hero__title">
            {t('about.title_pre')}<mark>{t('about.title_mark')}</mark>{t('about.title_post')}
          </h1>
          <p className="page-hero__lede">{t('about.lede')}</p>
        </div>
      </section>

      <section className="section" style={{ paddingTop: 0 }}>
        <div className="container">
          <div className="about-stats">
            <AboutStat
              value={stats.is_live ? stats.entreprises : null}
              suffix="+"
              fallback={t('about.stat1_num')}
              label={t('about.stat1_label')}
              decimalSep={decimalSep}
            />
            <AboutStat
              value={stats.is_live ? stats.particuliers : null}
              suffix="+"
              fallback={t('about.stat2_num')}
              label={t('about.stat2_label')}
              decimalSep={decimalSep}
            />
            <AboutStat value={levels.length} fallback={t('about.stat3_num')} label={t('about.stat3_label')} decimalSep={decimalSep} />
            <AboutStat
              value={stats.is_live && stats.satisfaction !== null ? stats.satisfaction : null}
              decimals={1}
              suffix="/5"
              fallback={t('about.stat4_num')}
              label={t('about.stat4_label')}
              decimalSep={decimalSep}
            />
          </div>
        </div>
      </section>

      <section className="section section--paper">
        <div className="container">
          <div className="section-head">
            <div>
              <span className="eyebrow">{t('about.philosophy_eyebrow')}</span>
              <h2 className="section-head__title">{t('about.philosophy_title')}</h2>
            </div>
          </div>
          <div className="grid-3">
            {[1, 2, 3].map((n) => (
              <PhilosophyCard key={n} title={t(`about.value${n}_title`)} text={t(`about.value${n}_text`)} />
            ))}
          </div>
        </div>
      </section>

      <section className="section">
        <div className="container">
          <div className="section-head">
            <div>
              <span className="eyebrow">{t('about.team_eyebrow')}</span>
              <h2 className="section-head__title">{t('about.team_title')}</h2>
            </div>
          </div>

          <Founder name={t('about.founder_name')} role={t('about.founder_role')} bio={t('about.founder_bio')} />

          <div className="cert-grid">
            <CertCard n={1} title={t('about.cert1_title')} meta={t('about.cert1_meta')} />
            <CertCard n={2} title={t('about.cert2_title')} meta={t('about.cert2_meta')} />
          </div>
        </div>
      </section>

      <section className="section section--paper">
        <div className="container">
          <div className="section-head">
            <div>
              <span className="eyebrow">{t('about.timeline_eyebrow')}</span>
              <h2 className="section-head__title">{t('about.timeline_title')}</h2>
            </div>
          </div>
          <div className="timeline">
            {[1, 2, 3, 4].map((n) => (
              <TimelineItem key={n} year={t(`about.tl${n}_year`)} title={t(`about.tl${n}_title`)} text={t(`about.tl${n}_text`)} />
            ))}
          </div>
        </div>
      </section>

      <section className="section">
        <div className="container">
          <CtaBand title={t('about.cta_title')} btnLabel={t('about.cta_btn')} />
        </div>
      </section>
    </>
  );
}

function AboutStat({
  value,
  decimals = 0,
  suffix = '',
  fallback,
  label,
  decimalSep,
}: {
  value: number | null;
  decimals?: number;
  suffix?: string;
  fallback: string;
  label: string;
  decimalSep: string;
}) {
  const reveal = useReveal<HTMLDivElement>();
  return (
    <div ref={reveal.ref} className={`about-stat ${reveal.className}`} style={reveal.style}>
      <LegacyStatNumber value={value} decimals={decimals} suffix={suffix} fallback={fallback} decimalSep={decimalSep} />
      <div className="stat__label">{label}</div>
    </div>
  );
}

function PhilosophyCard({ title, text }: { title: string; text: string }) {
  const reveal = useReveal<HTMLDivElement>();
  return (
    <div ref={reveal.ref} className={`value-card ${reveal.className}`} style={reveal.style}>
      <div className="value-card__icon">◆</div>
      <h3>{title}</h3>
      <p>{text}</p>
    </div>
  );
}

function Founder({ name, role, bio }: { name: string; role: string; bio: string }) {
  const reveal = useReveal<HTMLDivElement>();
  return (
    <div ref={reveal.ref} className={`founder ${reveal.className}`} style={reveal.style}>
      <div className="founder__avatar">
        <img src="/assets/img/team/Thomas-Lapierre.jpg" alt={name} />
      </div>
      <div>
        <h3>{name}</h3>
        <span className="founder__role">{role}</span>
        <p className="bio">{bio}</p>
      </div>
    </div>
  );
}

function CertCard({ n, title, meta }: { n: number; title: string; meta: string }) {
  const reveal = useReveal<HTMLAnchorElement>();
  const src = `/assets/img/certifications/Formation_iA_Niveau_${n}_Entreprise.jpg`;
  return (
    <a
      ref={reveal.ref}
      href={src}
      target="_blank"
      rel="noopener"
      className={`cert-card ${reveal.className}`}
      style={reveal.style}
    >
      <img src={src} alt={title} />
      <div className="cert-card__body">
        <div className="cert-card__title">{title}</div>
        <div className="cert-card__meta">{meta}</div>
      </div>
    </a>
  );
}

function TimelineItem({ year, title, text }: { year: string; title: string; text: string }) {
  const reveal = useReveal<HTMLDivElement>();
  return (
    <div ref={reveal.ref} className={`timeline-item ${reveal.className}`} style={reveal.style}>
      <time>{year}</time>
      <div>
        <h4>{title}</h4>
        <p>{text}</p>
      </div>
    </div>
  );
}

function CtaBand({ title, btnLabel }: { title: string; btnLabel: string }) {
  const reveal = useReveal<HTMLDivElement>();
  return (
    <div ref={reveal.ref} className={`cta-band ${reveal.className}`} style={reveal.style}>
      <h2>{title}</h2>
      <div className="cta-band__actions">
        <Link href="/contact" className="btn btn--signal">
          {btnLabel} <span className="btn__arrow">→</span>
        </Link>
      </div>
    </div>
  );
}
