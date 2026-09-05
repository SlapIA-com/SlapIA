import { Head, useForm } from '@inertiajs/react';
import { useTranslation } from '../../hooks/useTranslation';
import AuthLayout from '../../Layouts/AuthLayout';
import { Link } from '@inertiajs/react';

function Login() {
  const { t } = useTranslation();
  const { data, setData, post, processing, errors } = useForm({ email: '', password: '', remember: false });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    post('/login');
  }

  return (
    <>
      <Head title={t('auth.login_title')} />
      <form onSubmit={submit} className="space-y-4">
        {errors.email && <div className="rounded-lg bg-danger/10 px-4 py-2 text-sm text-danger">{errors.email}</div>}
        <div>
          <label className="mb-1.5 block text-sm font-medium text-ink-soft">{t('auth.label_email')}</label>
          <input type="email" required value={data.email} onChange={(e) => setData('email', e.target.value)} className="input" />
        </div>
        <div>
          <label className="mb-1.5 block text-sm font-medium text-ink-soft">{t('auth.label_password')}</label>
          <input type="password" required value={data.password} onChange={(e) => setData('password', e.target.value)} className="input" />
        </div>
        <div className="flex items-center justify-between text-sm">
          <label className="flex items-center gap-2 text-ink-fade">
            <input type="checkbox" checked={data.remember} onChange={(e) => setData('remember', e.target.checked)} />
            {t('auth.remember_me')}
          </label>
          <Link href="/mot-de-passe-oublie" className="text-signal-deep dark:text-signal">{t('auth.forgot_password')}</Link>
        </div>
        <button type="submit" disabled={processing} className="w-full rounded-full bg-signal py-3 text-sm font-semibold text-on-accent disabled:opacity-60">
          {t('auth.submit_login')}
        </button>
      </form>
      <style>{`.input { width:100%; border:1px solid rgb(var(--c-line)); border-radius:0.75rem; padding:0.65rem 0.9rem; background:rgb(var(--c-paper)); color:rgb(var(--c-ink)); font-size:0.9rem; } .input:focus { outline:2px solid rgb(var(--c-signal)); outline-offset:1px; }`}</style>
    </>
  );
}

Login.layout = (page: React.ReactNode) => {
  // Note: AuthLayout needs a translated title, resolved inline here to avoid
  // a hooks-outside-component issue.
  return <AuthLayoutWrapper>{page}</AuthLayoutWrapper>;
};

function AuthLayoutWrapper({ children }: { children: React.ReactNode }) {
  const { t } = useTranslation();
  return <AuthLayout title={t('auth.login_title')}>{children}</AuthLayout>;
}

export default Login;
