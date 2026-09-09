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
      {errors.email && (
        <div className="alert alert--error">
          <span>!</span>
          <span>{errors.email}</span>
        </div>
      )}
      <form onSubmit={submit} noValidate>
        <div className="field">
          <label htmlFor="email">{t('auth.label_email')}</label>
          <input type="email" id="email" name="email" required value={data.email} onChange={(e) => setData('email', e.target.value)} />
        </div>
        <div className="field" style={{ marginTop: 16 }}>
          <label htmlFor="password">{t('auth.label_new_password')}</label>
          <input type="password" id="password" name="password" required minLength={8} value={data.password} onChange={(e) => setData('password', e.target.value)} />
        </div>
        <div className="field" style={{ marginTop: 16 }}>
          <label htmlFor="password_confirmation">{t('dashboard.label_new_password')}</label>
          <input type="password" id="password_confirmation" name="password_confirmation" required value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} />
        </div>
        <button type="submit" disabled={processing} className="btn btn--primary btn--block" style={{ marginTop: 20 }}>
          {t('auth.submit_reset_exec')}
        </button>
      </form>
    </>
  );
}

ResetPassword.layout = (page: React.ReactNode) => <Wrapper>{page}</Wrapper>;
function Wrapper({ children }: { children: React.ReactNode }) {
  const { t } = useTranslation();
  return <AuthLayout title={t('auth.reset_title')}>{children}</AuthLayout>;
}

export default ResetPassword;
