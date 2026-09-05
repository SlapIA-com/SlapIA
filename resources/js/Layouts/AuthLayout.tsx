import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useTranslation } from '../hooks/useTranslation';

/** Layout minimal pour login / reset password — pas de header/footer marketing. */
export default function AuthLayout({ title, children }: { title: string; children: ReactNode }) {
  const { t } = useTranslation();
  return (
    <div className="flex min-h-screen items-center justify-center bg-surface-dark px-4 py-12">
      <div className="w-full max-w-md rounded-3xl border border-white/10 bg-mist p-8 text-ink shadow-2xl">
        <Link href="/" className="flex items-center justify-center gap-2 font-display text-lg font-semibold">
          <img src="/assets/img/brand/logo.svg" alt="" className="h-7 w-7" /> SlapIa
        </Link>
        <h1 className="mt-6 text-center font-display text-xl font-semibold text-ink">{title}</h1>
        <div className="mt-6">{children}</div>
        <Link href="/" className="mt-6 block text-center text-sm text-ink-fade hover:text-ink">← {t('auth.back_to_site')}</Link>
      </div>
    </div>
  );
}
