import { Link, usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useTranslation } from '../hooks/useTranslation';
import type { SharedProps } from '../types';

export default function DashboardLayout({ title, children }: { title: string; children: ReactNode }) {
  const { t } = useTranslation();
  const { auth, flash } = usePage<SharedProps>().props;

  return (
    <div className="min-h-screen bg-mist">
      <header className="border-b border-line bg-paper">
        <div className="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
          <Link href="/" className="flex items-center gap-2 font-display text-base font-semibold text-ink">
            <img src="/assets/img/brand/logo.svg" alt="" className="h-6 w-6" /> SlapIa
          </Link>
          <div className="flex items-center gap-4 text-sm">
            <span className="text-ink-fade">{auth.user?.name}</span>
            <Link href="/logout" method="post" as="button" className="font-medium text-ink-fade hover:text-danger">{t('nav.logout')}</Link>
          </div>
        </div>
      </header>
      <main className="mx-auto max-w-5xl px-6 py-10">
        <h1 className="font-display text-2xl font-semibold text-ink">{title}</h1>
        {flash.success && <div className="mt-4 rounded-lg bg-success/10 px-4 py-2 text-sm text-success">{flash.success}</div>}
        {flash.error && <div className="mt-4 rounded-lg bg-danger/10 px-4 py-2 text-sm text-danger">{flash.error}</div>}
        <div className="mt-6 space-y-6">{children}</div>
      </main>
    </div>
  );
}
