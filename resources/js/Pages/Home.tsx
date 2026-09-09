import { Head, Link } from '@inertiajs/react';
import { useRef } from 'react';
import { useTranslation } from '../hooks/useTranslation';
import { useReveal } from '../hooks/useReveal';
import HeroCanvas from '../Components/HeroCanvas';
import HeroReveal from '../Components/HeroReveal';
import RevealMark from '../Components/RevealMark';
import LegacyStatNumber from '../Components/LegacyStatNumber';
import ReviewsCarousel, { ReviewsNav, type ReviewsCarouselHandle } from '../Components/ReviewsCarousel';
import type { Level, PublicReview, Stats } from '../types';

/** Délai d'apparition par index dans un groupe (.reveal), plafonné à 5 — même règle que staggerGroups() dans main.js. */
function staggerDelay(i: number) {
  return Math.min(i, 5) * 0.08;
}

/** Port fidèle de pages/index.php (mêmes classes CSS legacy/style.css). */
export default function Home({ stats, reviews }: { stats: Stats; reviews: PublicReview[] }) {
  const { t, locale } = useTranslation();
  const levels: Level[] = t('levels');
  const decimalSep = locale === 'en' ? '.' : ',';
  const carouselRef = useRef<ReviewsCarouselHandle>(null);
  const ctaBand = useReveal();

  return (
    <>
      <Head title={t('home.meta_title')}>
        <meta name="description" content={t('home.meta_description')} />
      </Head>

      <section className="hero">
        <HeroCanvas />
        <div className="container hero__grid">
          <div>
            <HeroReveal as="span" className="eyebrow" delay="0.05s">
              {t('home.hero_eyebrow')}
            </HeroReveal>
            <HeroReveal as="h1" className="hero__title" delay="0.15s">
              {t('home.hero_title_line1')}
              <br />
              <RevealMark>{t('home.hero_title_mark')}</RevealMark>
            </HeroReveal>
            <HeroReveal as="p" className="hero__lede" delay="0.28s">
              {t('home.hero_lede')}
            </HeroReveal>
            <HeroReveal className="hero__ctas" delay="0.4s">
              <Link href="/formations" className="btn btn--primary">
                {t('home.cta_primary')} <span className="btn__arrow">→</span>
              </Link>
              <Link href="/contact" className="btn btn--ghost">
                {t('home.cta_secondary')}
              </Link>
            </HeroReveal>
            <HeroReveal className="hero__stats" delay="0.5s">
              <div className="stat">
                <LegacyStatNumber value={stats.is_live ? stats.entreprises : null} fallback={t('home.stat1_num')} suffix="+" decimalSep={decimalSep} />
                <div className="stat__label">{t('home.stat1_label')}</div>
              </div>
              <div className="stat">
                <LegacyStatNumber value={stats.is_live ? stats.particuliers : null} fallback={t('home.stat2_num')} suffix="+" decimalSep={decimalSep} />
                <div className="stat__label">{t('home.stat2_label')}</div>
              </div>
              <div className="stat">
                <LegacyStatNumber
                  value={stats.is_live && stats.satisfaction !== null ? stats.satisfaction : null}
                  decimals={1}
                  fallback={t('home.stat3_num')}
                  suffix="/5"
                  decimalSep={decimalSep}
                />
                <div className="stat__label">{t('home.stat3_label')}</div>
              </div>
            </HeroReveal>
          </div>

          <HeroReveal className="hero__panel hero-reveal--panel" delay="0.35s">
            <span className="hero__panel-label">{t('home.panel_label')}</span>
            <div className="hero__panel-list">
              {[1, 2, 3].map((n) => (
                <div className="hero__panel-item" key={n}>
                  <span className="hero__panel-tag">{t(`home.panel${n}_tag`)}</span>
                  <div>
                    <strong>{t(`home.panel${n}_title`)}</strong>
                    <span>{t(`home.panel${n}_meta`)}</span>
                  </div>
                </div>
              ))}
            </div>
          </HeroReveal>
        </div>
      </section>

      <section className="section section--dark">
        <div className="container">
          <div className="section-head">
            <div>
              <span className="eyebrow">{t('home.problem_eyebrow')}</span>
              <h2 className="section-head__title">{t('home.problem_title')}</h2>
            </div>
            <p className="section-head__note">{t('home.problem_note')}</p>
          </div>
          <div className="grid-3">
            {[1, 2, 3].map((n, i) => (
              <ProblemCard key={n} n={n} delay={staggerDelay(i)} title={t(`home.problem_card${n}_title`)} text={t(`home.problem_card${n}_text`)} />
            ))}
          </div>
        </div>
      </section>

      <section className="section">
        <div className="container">
          <div className="section-head">
            <div>
              <span className="eyebrow">{t('home.catalogue_eyebrow')}</span>
              <h2 className="section-head__title">{t('home.catalogue_title')}</h2>
            </div>
            <p className="section-head__note">{t('home.catalogue_note')}</p>
          </div>
          <div className="grid-3">
            {levels.map((level, i) => (
              <CourseCard key={level.anchor} level={level} delay={staggerDelay(i)} linkLabel={t('home.catalogue_link')} />
            ))}
          </div>
        </div>
      </section>

      <section className="section section--paper">
        <div className="container">
          <div className="section-head">
            <div>
              <span className="eyebrow">{t('home.method_eyebrow')}</span>
              <h2 className="section-head__title">{t('home.method_title')}</h2>
            </div>
            <p className="section-head__note">{t('home.method_note')}</p>
          </div>
          <div className="method-list">
            {[1, 2, 3, 4].map((n, i) => (
              <MethodItem key={n} num={String(n).padStart(2, '0')} delay={staggerDelay(i)} title={t(`home.method${n}_title`)} text={t(`home.method${n}_text`)} />
            ))}
          </div>
        </div>
      </section>

      <section className="section">
        <div className="container">
          <div className="section-head">
            <div>
              <span className="eyebrow">{t('home.testimonials_eyebrow')}</span>
              <h2 className="section-head__title">{t('home.testimonials_title')}</h2>
            </div>
            {reviews.length > 0 ? (
              <ReviewsNav onPrev={() => carouselRef.current?.prev()} onNext={() => carouselRef.current?.next()} />
            ) : (
              <p className="section-head__note">{t('home.testimonials_note')}</p>
            )}
          </div>

          {reviews.length > 0 ? (
            <ReviewsCarousel ref={carouselRef} reviews={reviews} />
          ) : (
            <div className="grid-3">
              {[1, 2, 3].map((n, i) => (
                <QuoteCard
                  key={n}
                  delay={staggerDelay(i)}
                  avatar={['MC', 'JL', 'SB'][i]}
                  text={t(`home.quote${n}_text`)}
                  name={t(`home.quote${n}_name`)}
                  role={t(`home.quote${n}_role`)}
                />
              ))}
            </div>
          )}
        </div>
      </section>

      <section className="section section--paper">
        <div className="container">
          <div className="section-head">
            <div>
              <span className="eyebrow">{t('home.pc_teaser_eyebrow')}</span>
              <h2 className="section-head__title">{t('home.pc_teaser_title')}</h2>
            </div>
            <p className="section-head__note">{t('home.pc_teaser_note')}</p>
          </div>
          <div className="grid-3">
            {[1, 2, 3].map((n, i) => (
              <PcCard key={n} delay={staggerDelay(i)} title={t(`home.pc_card${n}_title`)} text={t(`home.pc_card${n}_text`)} />
            ))}
          </div>
          <div style={{ marginTop: 32 }}>
            <Link href="/services-pc" className="btn btn--ghost">
              {t('home.pc_teaser_link')} <span className="btn__arrow">→</span>
            </Link>
          </div>
        </div>
      </section>

      <section className="section">
        <div className="container">
          <div ref={ctaBand.ref} className={`cta-band ${ctaBand.className}`}>
            <h2>{t('home.final_cta_title')}</h2>
            <div className="cta-band__actions">
              <Link href="/contact" className="btn btn--signal">
                {t('home.final_cta_primary')} <span className="btn__arrow">→</span>
              </Link>
              <Link href="/tarifs" className="btn btn--on-dark">
                {t('home.final_cta_secondary')}
              </Link>
            </div>
          </div>
        </div>
      </section>
    </>
  );
}

function ProblemCard({ n, delay, title, text }: { n: number; delay: number; title: string; text: string }) {
  const reveal = useReveal(delay);
  return (
    <div
      ref={reveal.ref}
      className={`value-card ${reveal.className}`}
      style={{ ...reveal.style, background: 'transparent', borderColor: 'rgba(255,255,255,0.15)', color: 'var(--on-dark)' }}
    >
      <div className="value-card__icon" style={{ background: 'rgba(255,255,255,0.08)' }}>
        {n === 1 ? '⚠' : n === 2 ? '⏱' : '◎'}
      </div>
      <h3 style={{ color: 'var(--on-dark)' }}>{title}</h3>
      <p style={{ color: 'rgba(245,242,250,0.65)' }}>{text}</p>
    </div>
  );
}

function CourseCard({ level, delay, linkLabel }: { level: Level; delay: number; linkLabel: string }) {
  // Pas de ref ici : Inertia <Link> ne garantit pas de forwarder sa ref vers
  // l'ancre — carte affichée directement (sans l'animation d'apparition au
  // scroll) plutôt que de risquer de rester bloquée à opacity:0.
  return (
    <Link
      href={`/formations#${level.anchor}`}
      className="course-card reveal is-visible"
      style={{ transitionDelay: `${delay}s`, textDecoration: 'none' }}
    >
      <div className="course-card__meta">
        <span className="tag tag--signal">Niveau {level.num}</span>
      </div>
      <h3>{level.title}</h3>
      <p className="desc">{level.teaser}</p>
      <div className="course-card__meta">
        {level.tools.map((tool) => (
          <span className="tag tag--ghost" key={tool}>
            {tool}
          </span>
        ))}
      </div>
      <div className="course-card__foot course-card__foot--end">
        <span className="course-card__link">
          {linkLabel} <span className="btn__arrow">→</span>
        </span>
      </div>
    </Link>
  );
}

function MethodItem({ num, delay, title, text }: { num: string; delay: number; title: string; text: string }) {
  const reveal = useReveal(delay);
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

function QuoteCard({ delay, avatar, text, name, role }: { delay: number; avatar: string; text: string; name: string; role: string }) {
  const reveal = useReveal(delay);
  return (
    <div ref={reveal.ref} className={`quote-card ${reveal.className}`} style={reveal.style}>
      <p>« {text} »</p>
      <div className="quote-card__who">
        <span className="quote-card__avatar">{avatar}</span>
        <div>
          <strong>{name}</strong>
          <span>{role}</span>
        </div>
      </div>
    </div>
  );
}

function PcCard({ delay, title, text }: { delay: number; title: string; text: string }) {
  const reveal = useReveal(delay);
  return (
    <div ref={reveal.ref} className={`value-card ${reveal.className}`} style={reveal.style}>
      <div className="value-card__icon">◆</div>
      <h3>{title}</h3>
      <p>{text}</p>
    </div>
  );
}
