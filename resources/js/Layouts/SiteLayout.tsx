import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState, type ReactNode } from 'react';
import { useTranslation } from '../hooks/useTranslation';
import type { SharedProps } from '../types';

const NAV_ITEMS: Array<{ href: string; key: string }> = [
  { href: '/', key: 'nav.home' },
  { href: '/formations', key: 'nav.courses' },
  { href: '/services-pc', key: 'nav.services' },
  { href: '/tarifs', key: 'nav.pricing' },
  { href: '/blog', key: 'nav.blog' },
  { href: '/a-propos', key: 'nav.about' },
  { href: '/contact', key: 'nav.contact' },
];

const LANG_LABELS: Record<string, string> = { fr: 'FR', en: 'EN', de: 'DE' };

function useTheme() {
  const [theme, setTheme] = useState<'light' | 'dark'>('light');

  useEffect(() => {
    const isDark = document.documentElement.classList.contains('dark');
    setTheme(isDark ? 'dark' : 'light');
  }, []);

  function toggle() {
    const next = theme === 'dark' ? 'light' : 'dark';
    setTheme(next);
    document.documentElement.classList.toggle('dark', next === 'dark');
    try {
      localStorage.setItem('slapia-theme', next);
    } catch {
      /* ignore */
    }
  }

  return { theme, toggle };
}

export default function SiteLayout({ children }: { children: ReactNode }) {
  const { t, locale } = useTranslation();
  const { auth } = usePage<SharedProps>().props;
  const { url } = usePage();
  const { theme, toggle } = useTheme();
  const [mobileOpen, setMobileOpen] = useState(false);

  const dashHref = auth.user?.role === 'admin' ? '/admin' : '/dashboard';
  const dashLabel = auth.user?.role === 'admin' ? t('nav.admin') : t('nav.dashboard');

  function switchLang(l: string) {
    const params = new URLSearchParams(window.location.search);
    params.set('lang', l);
    router.visit(`${window.location.pathname}?${params.toString()}`);
  }

  return (
    <div className="flex min-h-screen flex-col bg-mist text-ink">
      <header className="sticky top-0 z-40 border-b border-line bg-mist/90 backdrop-blur">
        <div className="mx-auto flex h-20 max-w-container items-center justify-between px-5 sm:px-8">
          <Link href="/" className="flex items-center gap-2 font-display text-lg font-semibold">
            <img src="/assets/img/brand/logo.svg" alt="" className="h-7 w-7" />
            SlapIa
          </Link>

          <nav className="hidden items-center gap-6 lg:flex" aria-label="Navigation principale">
            {NAV_ITEMS.map((item) => (
              <Link
                key={item.href}
                href={item.href}
                className={`text-sm font-medium transition-colors ${
                  url === item.href ? 'text-signal-deep dark:text-signal' : 'text-ink-soft hover:text-ink'
                }`}
              >
                {t(item.key)}
              </Link>
            ))}
          </nav>

          <div className="flex items-center gap-3">
            <div className="hidden items-center gap-1 rounded-full border border-line p-1 text-xs font-semibold sm:flex">
              {Object.entries(LANG_LABELS).map(([l, label]) => (
                <button
                  key={l}
                  onClick={() => switchLang(l)}
                  className={`rounded-full px-2 py-1 ${locale === l ? 'bg-signal text-on-accent' : 'text-ink-fade hover:text-ink'}`}
                >
                  {label}
                </button>
              ))}
            </div>

            <button
              onClick={toggle}
              aria-label={t('common.toggle_theme')}
              className="hidden h-9 w-9 items-center justify-center rounded-full border border-line text-ink-fade hover:text-ink sm:flex"
            >
              {theme === 'dark' ? '☀' : '☾'}
            </button>

            {auth.user ? (
              <div className="hidden items-center gap-3 lg:flex">
                <Link href={dashHref} className="text-sm font-medium text-ink-soft hover:text-ink">
                  {dashLabel}
                </Link>
                <Link href="/logout" method="post" as="button" className="text-sm font-medium text-ink-fade hover:text-danger">
                  {t('nav.logout')}
                </Link>
              </div>
            ) : (
              <Link href="/login" className="hidden text-sm font-medium text-ink-soft hover:text-ink lg:block">
                {t('nav.login')}
              </Link>
            )}

            <Link
              href="/contact"
              className="hidden rounded-full bg-ink px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-ink-soft dark:bg-white dark:text-ink lg:inline-flex"
            >
              {t('common.book_call')}
            </Link>

            <button
              className="flex h-9 w-9 items-center justify-center rounded-full border border-line lg:hidden"
              aria-label={t('common.open_menu')}
              onClick={() => setMobileOpen(true)}
            >
              ☰
            </button>
          </div>
        </div>
      </header>

      {mobileOpen && (
        <div className="fixed inset-0 z-50 flex flex-col bg-surface-dark p-6 text-on-dark lg:hidden">
          <div className="flex items-center justify-between">
            <Link href="/" className="font-display text-lg font-semibold">SlapIa</Link>
            <button aria-label={t('common.close_menu')} onClick={() => setMobileOpen(false)} className="text-2xl">✕</button>
          </div>
          <nav className="mt-10 flex flex-col gap-5">
            {NAV_ITEMS.map((item) => (
              <Link key={item.href} href={item.href} className="text-lg font-medium" onClick={() => setMobileOpen(false)}>
                {t(item.key)}
              </Link>
            ))}
          </nav>
          <div className="mt-8 flex gap-2">
            {Object.entries(LANG_LABELS).map(([l, label]) => (
              <button key={l} onClick={() => switchLang(l)} className={`rounded-full border border-white/20 px-3 py-1 text-sm ${locale === l ? 'bg-white/20' : ''}`}>
                {label}
              </button>
            ))}
          </div>
          <div className="mt-auto flex flex-col gap-3">
            {auth.user ? (
              <>
                <Link href={dashHref} className="rounded-full border border-white/20 py-3 text-center font-semibold">{dashLabel}</Link>
                <Link href="/logout" method="post" as="button" className="rounded-full border border-white/20 py-3 text-center font-semibold">{t('nav.logout')}</Link>
              </>
            ) : (
              <Link href="/login" className="rounded-full border border-white/20 py-3 text-center font-semibold">{t('nav.login')}</Link>
            )}
            <Link href="/contact" className="rounded-full bg-signal py-3 text-center font-semibold text-on-accent">{t('common.book_call')}</Link>
          </div>
        </div>
      )}

      <main className="flex-1">{children}</main>

      <footer className="border-t border-line bg-surface-dark text-on-dark">
        <Container>
          <div className="grid grid-cols-2 gap-10 py-14 sm:grid-cols-3 lg:grid-cols-5">
            <div className="col-span-2 lg:col-span-1">
              <div className="flex items-center gap-2 font-display text-lg font-semibold">
                <img src="/assets/img/brand/logo.svg" alt="" className="h-6 w-6" />
                SlapIa
              </div>
              <p className="mt-3 text-sm text-on-dark/65">{t('footer.tagline')}</p>
            </div>
            <FooterCol title={t('footer.courses_heading')} links={[
              ['/formations', t('footer.courses_all')],
              ['/formations#niveau-1', t('footer.course_link_1')],
              ['/formations#niveau-2', t('footer.course_link_2')],
              ['/formations#niveau-3', t('footer.course_link_3')],
            ]} />
            <FooterCol title={t('footer.services_heading')} links={[
              ['/services-pc#montage', t('footer.service_link_1')],
              ['/services-pc#devis', t('footer.service_link_2')],
              ['/services-pc#diagnostic', t('footer.service_link_3')],
            ]} />
            <FooterCol title={t('footer.company_heading')} links={[
              ['/a-propos', t('footer.link_about')],
              ['/tarifs', t('footer.link_pricing')],
              ['/contact', t('footer.link_contact')],
            ]} />
            <FooterCol title={t('footer.contact_heading')} links={[['mailto:contact@slapia.com', 'contact@slapia.com']]} />
          </div>
          <div className="flex flex-col gap-3 border-t border-white/10 py-6 text-xs text-on-dark/60 sm:flex-row sm:items-center sm:justify-between">
            <span>© {new Date().getFullYear()} {t('footer.copyright')}</span>
            <span className="flex gap-3">
              <Link href="/mentions-legales" className="hover:text-on-dark">{t('footer.legal_mentions')}</Link>
              <Link href="/confidentialite" className="hover:text-on-dark">{t('footer.legal_privacy')}</Link>
              <Link href="/cgv" className="hover:text-on-dark">{t('footer.legal_cgv')}</Link>
            </span>
          </div>
        </Container>
      </footer>
    </div>
  );
}

function Container({ children }: { children: ReactNode }) {
  return <div className="mx-auto max-w-container px-5 sm:px-8">{children}</div>;
}

function FooterCol({ title, links }: { title: string; links: Array<[string, string]> }) {
  return (
    <div>
      <h4 className="font-display text-sm font-semibold">{title}</h4>
      <ul className="mt-3 space-y-2 text-sm text-on-dark/65">
        {links.map(([href, label]) => (
          <li key={href}>
            <Link href={href} className="hover:text-on-dark">{label}</Link>
          </li>
        ))}
      </ul>
    </div>
  );
}
