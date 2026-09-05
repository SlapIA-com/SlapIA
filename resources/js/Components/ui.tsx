import { Link } from '@inertiajs/react';
import type { ComponentProps, ReactNode } from 'react';

export function Container({ children, className = '' }: { children: ReactNode; className?: string }) {
  return <div className={`mx-auto w-full max-w-container px-5 sm:px-8 ${className}`}>{children}</div>;
}

export function Eyebrow({ children }: { children: ReactNode }) {
  return (
    <span className="inline-block text-xs font-semibold uppercase tracking-[0.14em] text-signal-deep dark:text-signal">
      {children}
    </span>
  );
}

export function SectionHead({
  eyebrow,
  title,
  note,
  dark = false,
}: {
  eyebrow: string;
  title: string;
  note?: string;
  dark?: boolean;
}) {
  return (
    <div className="mb-10 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <Eyebrow>{eyebrow}</Eyebrow>
        <h2 className={`mt-2 max-w-2xl font-display text-2xl font-semibold leading-tight sm:text-3xl ${dark ? 'text-on-dark' : 'text-ink'}`}>
          {title}
        </h2>
      </div>
      {note && <p className={`max-w-sm text-sm ${dark ? 'text-on-dark/65' : 'text-ink-fade'}`}>{note}</p>}
    </div>
  );
}

type ButtonVariant = 'primary' | 'ghost' | 'signal' | 'on-dark';

const variantClasses: Record<ButtonVariant, string> = {
  primary: 'bg-ink text-white hover:bg-ink-soft dark:bg-white dark:text-ink dark:hover:bg-mist',
  signal: 'bg-signal text-on-accent hover:bg-signal-deep hover:text-white',
  ghost: 'border border-line-strong text-ink hover:border-signal hover:text-signal-deep dark:text-on-dark',
  'on-dark': 'bg-white/10 text-on-dark border border-white/20 hover:bg-white/20',
};

export function Button({
  href,
  variant = 'primary',
  block = false,
  children,
  className = '',
  ...rest
}: {
  href: string;
  variant?: ButtonVariant;
  block?: boolean;
  children: ReactNode;
  className?: string;
} & Omit<ComponentProps<typeof Link>, 'href' | 'children' | 'className'>) {
  return (
    <Link
      href={href}
      className={`group inline-flex items-center justify-center gap-2 rounded-full px-6 py-3 text-sm font-semibold transition-colors duration-200 ${block ? 'w-full' : ''} ${variantClasses[variant]} ${className}`}
      {...rest}
    >
      {children}
      <span className="transition-transform duration-200 group-hover:translate-x-0.5">→</span>
    </Link>
  );
}

export function Tag({ children, signal = false }: { children: ReactNode; signal?: boolean }) {
  return (
    <span
      className={`inline-block rounded-full px-3 py-1 text-xs font-medium ${
        signal ? 'bg-signal/15 text-signal-deep dark:text-signal' : 'border border-line-strong text-ink-fade'
      }`}
    >
      {children}
    </span>
  );
}

export function ValueCard({ icon = '◆', title, text }: { icon?: string; title: string; text: string }) {
  return (
    <div className="rounded-2xl border border-line bg-paper p-6">
      <div className="mb-4 flex h-10 w-10 items-center justify-center rounded-full bg-signal/10 text-signal-deep dark:text-signal">
        {icon}
      </div>
      <h3 className="font-display text-lg font-semibold text-ink">{title}</h3>
      <p className="mt-2 text-sm leading-relaxed text-ink-fade">{text}</p>
    </div>
  );
}

export function StatNumber({ value, decimals = 0, suffix = '', fallback }: { value: number | null; decimals?: number; suffix?: string; fallback: string }) {
  if (value === null) {
    return <div className="font-display text-3xl font-bold text-ink sm:text-4xl">{fallback}</div>;
  }
  const display = value.toLocaleString('fr-FR', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }) + suffix;
  return <div className="font-display text-3xl font-bold text-ink sm:text-4xl">{display}</div>;
}

export function Divider() {
  return <hr className="my-6 border-line" />;
}
