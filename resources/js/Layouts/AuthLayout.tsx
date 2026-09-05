import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useTranslation } from '../hooks/useTranslation';

/**
 * Port fidèle de includes/auth-header.php + includes/auth-footer.php :
 * layout minimal et immersif pour login / reset-password (pas de nav, pas
 * de footer du site, toujours en mode sombre — voir assets/css/auth.css).
 * Volontairement séparé de SiteLayout, comme dans l'ancien site.
 */
export default function AuthLayout({ title, children }: { title: string; children: ReactNode }) {
  const { t } = useTranslation();

  return (
    <div className="auth-screen">
      <div className="auth-mesh" aria-hidden="true">
        <div className="auth-blob auth-blob--1" />
        <div className="auth-blob auth-blob--2" />
        <div className="auth-blob auth-blob--3" />
      </div>

      <Link href="/" className="auth-logo">
        <img src="/assets/img/brand/logo.svg" alt="" className="auth-logo__mark" /> SlapIa
      </Link>

      <main className="auth-stage">
        <div className="auth-card">
          <h1 className="auth-title">{title}</h1>
          {children}
        </div>
      </main>

      <Link href="/" className="auth-back">
        ← {t('auth.back_to_site')}
      </Link>
    </div>
  );
}
