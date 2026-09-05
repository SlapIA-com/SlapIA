import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type ReactNode } from 'react';
import { useTranslation } from '../hooks/useTranslation';
import type { SharedProps } from '../types';

/**
 * Port fidèle de includes/header.php + includes/footer.php (mêmes classes
 * CSS que legacy/style.css : .site-header, .rail, .nav, .btn--primary,
 * .mobile-menu, .site-footer, etc.) — remplace la version précédente qui
 * réinterprétait le design en utilitaires Tailwind et ne ressemblait plus
 * à l'ancien site.
 */

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

function applyTheme(theme: 'light' | 'dark') {
  document.documentElement.setAttribute('data-theme', theme);
  document.documentElement.classList.toggle('dark', theme === 'dark');
  try {
    localStorage.setItem('slapia-theme', theme);
  } catch {
    /* ignore */
  }
}

function useTheme() {
  const [theme, setTheme] = useState<'light' | 'dark'>('light');

  useEffect(() => {
    const attr = document.documentElement.getAttribute('data-theme');
    setTheme(attr === 'dark' ? 'dark' : 'light');
  }, []);

  function toggle() {
    const next = theme === 'dark' ? 'light' : 'dark';
    setTheme(next);
    applyTheme(next);
  }

  return { theme, toggle };
}

/** Port de la partie scroll de main.js : header ombré + jauge de progression de lecture (.rail / .progress-mobile). */
function useScrollChrome() {
  const [scrolled, setScrolled] = useState(false);
  const [pct, setPct] = useState(0);

  useEffect(() => {
    function onScroll() {
      const scrollTop = window.scrollY || document.documentElement.scrollTop;
      const docHeight = document.documentElement.scrollHeight - window.innerHeight;
      const p = docHeight > 0 ? Math.min(100, Math.max(0, (scrollTop / docHeight) * 100)) : 0;
      setScrolled(scrollTop > 8);
      setPct(p);
    }
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll);
    return () => {
      window.removeEventListener('scroll', onScroll);
      window.removeEventListener('resize', onScroll);
    };
  }, []);

  return { scrolled, pct };
}

function ThemeToggleIcon() {
  return (
    <>
      <svg className="theme-toggle__icon theme-toggle__icon--sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round">
        <circle cx="12" cy="12" r="4.5" />
        <path d="M12 2.5v2.3M12 19.2v2.3M4.4 4.4l1.6 1.6M18 18l1.6 1.6M2.5 12h2.3M19.2 12h2.3M4.4 19.6l1.6-1.6M18 6l1.6-1.6" />
      </svg>
      <svg className="theme-toggle__icon theme-toggle__icon--moon" viewBox="0 0 24 24" fill="currentColor">
        <path d="M20 14.5A8.5 8.5 0 019.5 4a8.5 8.5 0 1010.5 10.5z" />
      </svg>
    </>
  );
}

export default function SiteLayout({ children }: { children: ReactNode }) {
  const { t, locale } = useTranslation();
  const { auth } = usePage<SharedProps>().props;
  const { url } = usePage();
  const { theme, toggle } = useTheme();
  const { scrolled, pct } = useScrollChrome();
  const [mobileOpen, setMobileOpen] = useState(false);
  const [langOpen, setLangOpen] = useState(false);
  const [userOpen, setUserOpen] = useState(false);
  const langMenuRef = useRef<HTMLDivElement>(null);
  const userMenuRef = useRef<HTMLDivElement>(null);

  const dashHref = auth.user?.role === 'admin' ? '/admin' : '/dashboard';
  const dashLabel = auth.user?.role === 'admin' ? t('nav.admin') : t('nav.dashboard');

  useEffect(() => {
    document.body.style.overflow = mobileOpen ? 'hidden' : '';
  }, [mobileOpen]);

  useEffect(() => {
    function onDocClick(e: MouseEvent) {
      if (langMenuRef.current && !langMenuRef.current.contains(e.target as Node)) setLangOpen(false);
      if (userMenuRef.current && !userMenuRef.current.contains(e.target as Node)) setUserOpen(false);
    }
    document.addEventListener('click', onDocClick);
    return () => document.removeEventListener('click', onDocClick);
  }, []);

  function switchLang(l: string) {
    const params = new URLSearchParams(window.location.search);
    params.set('lang', l);
    router.visit(`${window.location.pathname}?${params.toString()}`);
  }

  return (
    <>
      <div className="rail" aria-hidden="true">
        <img src="/assets/img/brand/logo.svg" alt="" className="rail__mark" />
        <span className="rail__label">FORMATIONS IA — SERVICES PC</span>
        <span className="rail__track">
          <span className="rail__fill" style={{ height: `${pct}%` }} />
        </span>
        <span className="rail__pct">{Math.round(pct)}%</span>
      </div>
      <div className="progress-mobile" aria-hidden="true">
        <span className="progress-mobile__fill" style={{ width: `${pct}%` }} />
      </div>

      <header className={`site-header${scrolled ? ' is-scrolled' : ''}`}>
        <div className="container">
          <Link href="/" className="logo">
            <img src="/assets/img/brand/logo.svg" alt="" className="logo__mark" /> SlapIa
          </Link>

          <nav className="nav" aria-label="Navigation principale">
            {NAV_ITEMS.map((item) => (
              <Link key={item.href} href={item.href} className={`nav__link${url === item.href ? ' is-active' : ''}`}>
                {t(item.key)}
              </Link>
            ))}
          </nav>

          <div className="header__actions">
            <div className={`lang-menu${langOpen ? ' is-open' : ''}`} ref={langMenuRef}>
              <button
                type="button"
                className="lang-menu__trigger"
                aria-haspopup="true"
                aria-expanded={langOpen}
                aria-label={t('common.switch_lang')}
                title={t('common.switch_lang')}
                onClick={(e) => {
                  e.stopPropagation();
                  setLangOpen((o) => !o);
                }}
              >
                {LANG_LABELS[locale] ?? locale.toUpperCase()}
              </button>
              <div className="lang-menu__dropdown">
                {Object.entries(LANG_LABELS)
                  .filter(([l]) => l !== locale)
                  .map(([l, label]) => (
                    <button key={l} type="button" className="lang-menu__link" onClick={() => switchLang(l)}>
                      {label}
                    </button>
                  ))}
              </div>
            </div>

            <button className="theme-toggle" type="button" aria-label={t('common.toggle_theme')} title={t('common.toggle_theme')} onClick={toggle}>
              <ThemeToggleIcon />
            </button>

            {auth.user ? (
              <div className={`user-menu${userOpen ? ' is-open' : ''}`} ref={userMenuRef}>
                <button
                  type="button"
                  className="user-menu__trigger"
                  aria-haspopup="true"
                  aria-expanded={userOpen}
                  aria-label={t('nav.account_menu')}
                  onClick={(e) => {
                    e.stopPropagation();
                    setUserOpen((o) => !o);
                  }}
                >
                  <img src={`/api/avatar/${auth.user.id}`} alt="" className="user-menu__avatar" />
                </button>
                <div className="user-menu__dropdown">
                  <div className="user-menu__name">{auth.user.name}</div>
                  <Link href={dashHref} className="user-menu__link">
                    {dashLabel}
                  </Link>
                  <Link href="/logout" method="post" as="button" className="user-menu__link user-menu__link--danger">
                    {t('nav.logout')}
                  </Link>
                </div>
              </div>
            ) : (
              <Link href="/login" className="btn btn--ghost">
                {t('nav.login')}
              </Link>
            )}
            <Link href="/contact" className="btn btn--primary">
              {t('common.book_call')} <span className="btn__arrow">→</span>
            </Link>
            <button className="nav-toggle" aria-label={t('common.open_menu')} onClick={() => setMobileOpen(true)}>
              <span />
            </button>
          </div>
        </div>
      </header>

      <div className={`mobile-menu${mobileOpen ? ' is-open' : ''}`}>
        <div className="mobile-menu__top">
          <Link href="/" className="logo" onClick={() => setMobileOpen(false)}>
            <img src="/assets/img/brand/logo.svg" alt="" className="logo__mark" /> SlapIa
          </Link>
          <div style={{ display: 'flex', gap: 10, alignItems: 'center' }}>
            <button className="theme-toggle theme-toggle--on-dark" type="button" aria-label={t('common.toggle_theme')} title={t('common.toggle_theme')} onClick={toggle}>
              <ThemeToggleIcon />
            </button>
            <button className="mobile-menu__close" aria-label={t('common.close_menu')} onClick={() => setMobileOpen(false)}>
              ✕
            </button>
          </div>
        </div>
        <nav className="mobile-menu__links" aria-label="Navigation mobile">
          {NAV_ITEMS.map((item) => (
            <Link key={item.href} href={item.href} className={url === item.href ? 'is-active' : ''} onClick={() => setMobileOpen(false)}>
              {t(item.key)}
            </Link>
          ))}
        </nav>
        <div className="mobile-menu__lang">
          {Object.entries(LANG_LABELS).map(([l, label]) => (
            <button
              key={l}
              type="button"
              className={`lang-switch__link lang-switch__link--on-dark${locale === l ? ' is-active' : ''}`}
              onClick={() => switchLang(l)}
            >
              {label}
            </button>
          ))}
        </div>
        <div className="mobile-menu__foot">
          {auth.user ? (
            <>
              <Link href={dashHref} className="btn btn--on-dark btn--block" style={{ marginBottom: 10 }} onClick={() => setMobileOpen(false)}>
                {dashLabel}
              </Link>
              <Link href="/logout" method="post" as="button" className="btn btn--on-dark btn--block" style={{ marginBottom: 10 }}>
                {t('nav.logout')}
              </Link>
            </>
          ) : (
            <Link href="/login" className="btn btn--on-dark btn--block" style={{ marginBottom: 10 }} onClick={() => setMobileOpen(false)}>
              {t('nav.login')}
            </Link>
          )}
          <Link href="/contact" className="btn btn--signal btn--block" onClick={() => setMobileOpen(false)}>
            {t('common.book_call')}
          </Link>
        </div>
      </div>

      {children}

      <footer className="site-footer">
        <div className="container">
          <div className="footer__top">
            <div className="footer__brand">
              <Link href="/" className="logo">
                <img src="/assets/img/brand/logo.svg" alt="" className="logo__mark" /> SlapIa
              </Link>
              <p>{t('footer.tagline')}</p>
            </div>
            <div className="footer__col">
              <h4>{t('footer.courses_heading')}</h4>
              <ul>
                <li><Link href="/formations">{t('footer.courses_all')}</Link></li>
                <li><Link href="/formations#niveau-1">{t('footer.course_link_1')}</Link></li>
                <li><Link href="/formations#niveau-2">{t('footer.course_link_2')}</Link></li>
                <li><Link href="/formations#niveau-3">{t('footer.course_link_3')}</Link></li>
              </ul>
            </div>
            <div className="footer__col">
              <h4>{t('footer.services_heading')}</h4>
              <ul>
                <li><Link href="/services-pc#montage">{t('footer.service_link_1')}</Link></li>
                <li><Link href="/services-pc#devis">{t('footer.service_link_2')}</Link></li>
                <li><Link href="/services-pc#diagnostic">{t('footer.service_link_3')}</Link></li>
              </ul>
            </div>
            <div className="footer__col">
              <h4>{t('footer.company_heading')}</h4>
              <ul>
                <li><Link href="/a-propos">{t('footer.link_about')}</Link></li>
                <li><Link href="/tarifs">{t('footer.link_pricing')}</Link></li>
                <li><Link href="/contact">{t('footer.link_contact')}</Link></li>
              </ul>
            </div>
            <div className="footer__col">
              <h4>{t('footer.contact_heading')}</h4>
              <ul>
                <li><a href="mailto:contact@slapia.com">contact@slapia.com</a></li>
              </ul>
            </div>
          </div>
          <div className="footer__bottom">
            <span>© {new Date().getFullYear()} {t('footer.copyright')}</span>
            <span>
              <Link href="/mentions-legales">{t('footer.legal_mentions')}</Link> ·{' '}
              <Link href="/confidentialite">{t('footer.legal_privacy')}</Link> ·{' '}
              <Link href="/cgv">{t('footer.legal_cgv')}</Link>
            </span>
          </div>
        </div>
      </footer>
    </>
  );
}
