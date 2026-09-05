import { Head, Link } from '@inertiajs/react';
import { useTranslation } from '../hooks/useTranslation';

/** Rendu pour toute erreur HTTP (404 en particulier) — voir bootstrap/app.php ->withExceptions(). */
export default function Error({ status }: { status: number }) {
  const { t } = useTranslation();
  const is404 = status === 404;

  return (
    <>
      <Head title={t('error404.meta_title')}><meta name="description" content={t('error404.meta_description')} /></Head>

      <section className="page-hero">
        {is404 && (
          <div className="page-hero-canvas page-hero-canvas--broken" aria-hidden="true">
            <svg viewBox="0 0 300 160" className="broken-link-svg">
              <line x1="40" y1="80" x2="140" y2="50" stroke="var(--signal)" strokeWidth="2" opacity="0.5" />
              <line x1="140" y1="50" x2="150" y2="90" stroke="var(--forest)" strokeWidth="2" strokeDasharray="4 6" opacity="0.5" />
              <line x1="180" y1="100" x2="250" y2="70" stroke="var(--signal-pink)" strokeWidth="2" opacity="0" className="broken-link-svg__spark" />
              <circle cx="40" cy="80" r="6" fill="var(--signal)" />
              <circle cx="140" cy="50" r="6" fill="var(--forest)" />
              <circle cx="180" cy="100" r="6" fill="var(--signal-pink)" className="broken-link-svg__node" />
              <circle cx="250" cy="70" r="6" fill="var(--signal-pink)" opacity="0.35" />
            </svg>
          </div>
        )}
        <div className="container">
          <span className="eyebrow">{is404 ? t('error404.eyebrow') : `Erreur ${status}`}</span>
          <h1 className="page-hero__title">
            {t('error404.title_pre')}<mark>{t('error404.title_mark')}</mark>{t('error404.title_post')}
          </h1>
          <p className="page-hero__lede">{t('error404.lede')}</p>
        </div>
      </section>

      <section className="section" style={{ paddingTop: 0 }}>
        <div className="container">
          <div className="grid-4">
            <Link href="/" className="value-card reveal is-visible" style={{ textDecoration: 'none' }}>
              <div className="value-card__icon">◆</div>
              <h3>{t('error404.link_home')}</h3>
            </Link>
            <Link href="/formations" className="value-card reveal is-visible" style={{ textDecoration: 'none' }}>
              <div className="value-card__icon">◆</div>
              <h3>{t('error404.link_courses')}</h3>
            </Link>
            <Link href="/services-pc" className="value-card reveal is-visible" style={{ textDecoration: 'none' }}>
              <div className="value-card__icon">◆</div>
              <h3>{t('error404.link_services')}</h3>
            </Link>
            <Link href="/contact" className="value-card reveal is-visible" style={{ textDecoration: 'none' }}>
              <div className="value-card__icon">◆</div>
              <h3>{t('error404.link_contact')}</h3>
            </Link>
          </div>
        </div>
      </section>
    </>
  );
}
