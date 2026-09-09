import { Head, Link } from '@inertiajs/react';
import { useTranslation } from '../hooks/useTranslation';
import { useReveal } from '../hooks/useReveal';
import type { Level } from '../types';

/** Port fidèle de pages/formations.php (mêmes classes CSS legacy/style.css). */
export default function Courses() {
  const { t } = useTranslation();
  const levels: Level[] = t('levels');
  const ctaBand = useReveal();

  return (
    <>
      <Head title={t('courses_page.meta_title')}>
        <meta name="description" content={t('courses_page.meta_description')} />
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
          <span className="eyebrow">{t('courses_page.eyebrow')}</span>
          <h1 className="page-hero__title">
            {t('courses_page.title_pre')}
            <mark>{t('courses_page.title_mark')}</mark>
            {t('courses_page.title_post')}
          </h1>
          <p className="page-hero__lede">{t('courses_page.lede')}</p>
        </div>
      </section>

      <section className="section" style={{ paddingTop: 0 }}>
        <div className="container">
          <div className="grid-3">
            {levels.map((level) => (
              <LevelCard key={level.anchor} level={level} cta={t('courses_page.levels_cta')} />
            ))}
          </div>
        </div>
      </section>

      <section className="section section--paper">
        <div className="container">
          {levels.map((level) => (
            <LevelBlock
              key={level.anchor}
              level={level}
              moduleLabel={t('courses_page.module_label')}
              themeLabel={t('courses_page.theme_label')}
              actionLabel={t('courses_page.action_label')}
              toolsLabel={t('courses_page.tools_label')}
              noTool={t('courses_page.no_tool')}
            />
          ))}
        </div>
      </section>

      <section className="section section--paper">
        <div className="container">
          <p style={{ textAlign: 'center', color: 'var(--ink-fade)', marginBottom: 28 }}>
            {t('courses_page.vip_title')}{' '}
            <Link href="/tarifs#mentorat" style={{ color: 'var(--forest)', textDecoration: 'underline', textUnderlineOffset: '2px' }}>
              {t('courses_page.vip_cta')} →
            </Link>
          </p>
          <div ref={ctaBand.ref} className={`cta-band ${ctaBand.className}`}>
            <h2>{t('courses_page.bottom_cta_title')}</h2>
            <div className="cta-band__actions">
              <Link href="/contact" className="btn btn--signal">
                {t('courses_page.bottom_cta_btn')} <span className="btn__arrow">→</span>
              </Link>
            </div>
          </div>
        </div>
      </section>
    </>
  );
}

function LevelCard({ level, cta }: { level: Level; cta: string }) {
  // Pas de ref ici : ancre de même page, pas de <Link> Inertia nécessaire —
  // affichée directement (sans l'animation d'apparition au scroll), comme
  // Home.tsx's CourseCard.
  return (
    <a href={`#${level.anchor}`} className="course-card reveal is-visible" style={{ textDecoration: 'none' }}>
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
          {cta} <span className="btn__arrow">→</span>
        </span>
      </div>
    </a>
  );
}

function LevelBlock({
  level,
  moduleLabel,
  themeLabel,
  actionLabel,
  toolsLabel,
  noTool,
}: {
  level: Level;
  moduleLabel: string;
  themeLabel: string;
  actionLabel: string;
  toolsLabel: string;
  noTool: string;
}) {
  const reveal = useReveal();
  return (
    <div ref={reveal.ref} className={`level-block ${reveal.className}`} style={reveal.style} id={level.anchor}>
      <div className="level-block__head">
        <span className="level-block__num">{level.num}</span>
        <div>
          <h3>{level.detail_title}</h3>
          <p>{level.detail_subtitle}</p>
        </div>
      </div>
      <div className="curriculum-table-wrap">
        <table className="curriculum-table">
          <thead>
            <tr>
              <th>{moduleLabel}</th>
              <th>{themeLabel}</th>
              <th>{actionLabel}</th>
              <th>{toolsLabel}</th>
            </tr>
          </thead>
          <tbody>
            {level.modules.map((m) => (
              <tr key={m.code}>
                <td>{m.code}</td>
                <td>{m.theme}</td>
                <td>{m.desc}</td>
                <td>
                  {m.tools.length === 0 ? (
                    <span className="curriculum-table__none">{noTool}</span>
                  ) : (
                    <div className="curriculum-table__tools">
                      {m.tools.map((tool) => (
                        <span className="tag" key={tool}>
                          {tool}
                        </span>
                      ))}
                    </div>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
