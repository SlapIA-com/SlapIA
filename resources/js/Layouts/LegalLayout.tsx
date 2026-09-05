import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useTranslation } from '../hooks/useTranslation';

const NAV_ITEMS = [
  { href: '/mentions-legales', labelKey: 'legal_common.nav_mentions' },
  { href: '/confidentialite', labelKey: 'legal_common.nav_privacy' },
  { href: '/cgv', labelKey: 'legal_common.nav_cgv' },
] as const;

export default function LegalLayout({ title, lede, children }: { title: string; lede?: string; children: ReactNode }) {
  const { t } = useTranslation();
  const { url } = usePage();
  const currentPath = url.split(/[?#]/)[0];

  return (
    <>
      <section className="page-hero">
        <div className="container">
          <span className="eyebrow">{t('legal_common.eyebrow')}</span>
          <h1 className="page-hero__title">{title}</h1>
          {lede && <p className="page-hero__lede">{lede}</p>}
        </div>
      </section>

      <section className="section" style={{ paddingTop: 0 }}>
        <div className="container legal">
          <p className="legal-meta">{t('legal_common.updated')}</p>

          <nav className="legal-nav" aria-label="Legal pages">
            {NAV_ITEMS.map((item) => (
              <Link key={item.href} href={item.href} className={item.href === currentPath ? 'tag' : 'tag tag--ghost'}>
                {t(item.labelKey)}
              </Link>
            ))}
          </nav>

          {children}
        </div>
      </section>
    </>
  );
}
