import { Head, useForm } from '@inertiajs/react';
import { useTranslation } from '../../hooks/useTranslation';
import AuthLayout from '../../Layouts/AuthLayout';

function ResetPassword({ token, email }: { token: string; email: string }) {
  const { t } = useTranslation();
  const { data, setData, post, processing, errors } = useForm({
    token,
    email,
    password: '',
    password_confirmation: '',
  });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    post('/reinitialiser-mot-de-passe');
  }

  return (
    <>
      <Head title={t('auth.reset_title')} />
      {errors.email && <div className="mb-4 rounded-lg bg-danger/10 px-4 py-2 text-sm text-danger">{errors.email}</div>}
      <form onSubmit={submit} className="space-y-4">
        <div>
          <label className="mb-1.5 block text-sm font-medium text-ink-soft">{t('auth.label_email')}</label>
          <input type="email" required value={data.email} onChange={(e) => setData('email', e.target.value)} className="input" />
        </div>
        <div>
          <label className="mb-1.5 block text-sm font-medium text-ink-soft">{t('auth.label_new_password')}</label>
          <input type="password" required minLength={8} value={data.password} onChange={(e) => setData('password', e.target.value)} className="input" />
        </div>
        <div>
          <label className="mb-1.5 block text-sm font-medium text-ink-soft">{t('dashboard.label_new_password')}</label>
          <input type="password" required value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} className="input" />
        </div>
        <button type="submit" disabled={processing} className="w-full rounded-full bg-signal py-3 text-sm font-semibold text-on-accent disabled:opacity-60">
          {t('auth.submit_reset_exec')}
        </button>
      </form>
      <style>{`.input { width:100%; border:1px solid rgb(var(--c-line)); border-radius:0.75rem; padding:0.65rem 0.9rem; background:rgb(var(--c-paper)); color:rgb(var(--c-ink)); font-size:0.9rem; }`}</style>
    </>
  );
}

ResetPassword.layout = (page: React.ReactNode) => <Wrapper>{page}</Wrapper>;
function Wrapper({ children }: { children: React.ReactNode }) {
  const { t } = useTranslation();
  return <AuthLayout title={t('auth.reset_title')}>{children}</AuthLayout>;
}

export default ResetPassword;
