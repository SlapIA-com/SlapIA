import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useTranslation } from '../hooks/useTranslation';
import { Container, Eyebrow } from '../Components/ui';

export default function LegalLayout({ title, children }: { title: string; children: ReactNode }) {
  const { t } = useTranslation();

  return (
    <section className="py-16">
      <Container className="max-w-3xl">
        <Eyebrow>{t('legal_common.eyebrow')}</Eyebrow>
        <h1 className="mt-3 font-display text-3xl font-bold text-ink">{title}</h1>
        <p className="mt-2 text-xs text-ink-fade">{t('legal_common.updated')}</p>
        <nav className="mt-6 flex flex-wrap gap-4 border-b border-line pb-6 text-sm font-medium">
          <Link href="/mentions-legales" className="text-ink-soft hover:text-signal-deep">{t('legal_common.nav_mentions')}</Link>
          <Link href="/confidentialite" className="text-ink-soft hover:text-signal-deep">{t('legal_common.nav_privacy')}</Link>
          <Link href="/cgv" className="text-ink-soft hover:text-signal-deep">{t('legal_common.nav_cgv')}</Link>
        </nav>
        <div className="prose prose-slate mt-8 max-w-none prose-headings:font-display prose-headings:text-ink prose-p:text-ink-soft dark:prose-invert">
          {children}
        </div>
      </Container>
    </section>
  );
}
