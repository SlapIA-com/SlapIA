import { Head, useForm } from '@inertiajs/react';
import { useTranslation } from '../../hooks/useTranslation';
import AuthLayout from '../../Layouts/AuthLayout';
import { Link } from '@inertiajs/react';
import Turnstile from '../../Components/Turnstile';

function Login({ turnstileSiteKey }: { turnstileSiteKey: string }) {
  const { t } = useTranslation();
  const { data, setData, post, processing, errors } = useForm({
    email: '',
    password: '',
    remember: false,
    'cf-turnstile-response': '',
  });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    post('/login');
  }

  return (
    <>
      <Head title={t('auth.login_title')} />
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
          <label htmlFor="password">{t('auth.label_password')}</label>
          <input type="password" id="password" name="password" required value={data.password} onChange={(e) => setData('password', e.target.value)} />
        </div>
        <label className="consent-check" style={{ marginTop: 16 }}>
          <input type="checkbox" checked={data.remember} onChange={(e) => setData('remember', e.target.checked)} />
          <span>{t('auth.remember_me')}</span>
        </label>

        {turnstileSiteKey && (
          <div className="contact-turnstile-wrap" style={{ marginTop: 16 }}>
            <Turnstile siteKey={turnstileSiteKey} onVerify={(token) => setData('cf-turnstile-response', token)} />
          </div>
        )}

        <button
          type="submit"
          disabled={processing || (!!turnstileSiteKey && !data['cf-turnstile-response'])}
          className="btn btn--primary btn--block"
          style={{ marginTop: 20 }}
        >
          {t('auth.submit_login')}
        </button>
      </form>

      <p className="auth-links">
        <Link href="/mot-de-passe-oublie">{t('auth.forgot_password')}</Link>
      </p>
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
