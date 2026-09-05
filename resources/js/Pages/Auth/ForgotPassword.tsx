import { Head, useForm, usePage } from '@inertiajs/react';
import { useTranslation } from '../../hooks/useTranslation';
import AuthLayout from '../../Layouts/AuthLayout';
import type { SharedProps } from '../../types';

function ForgotPassword() {
  const { t } = useTranslation();
  const { flash } = usePage<SharedProps>().props;
  const { data, setData, post, processing, errors } = useForm({ email: '' });

  function submit(e: React.FormEvent) {
    e.preventDefault();
    post('/mot-de-passe-oublie');
  }

  return (
    <>
      <Head title={t('auth.reset_title')} />
      {flash.success && (
        <div className="alert alert--success">
          <span>✓</span>
          <span>{flash.success}</span>
        </div>
      )}
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
        <button type="submit" disabled={processing} className="btn btn--primary btn--block" style={{ marginTop: 20 }}>
          {t('auth.submit_reset_request')}
        </button>
      </form>
    </>
  );
}

ForgotPassword.layout = (page: React.ReactNode) => <Wrapper>{page}</Wrapper>;
function Wrapper({ children }: { children: React.ReactNode }) {
  const { t } = useTranslation();
  return <AuthLayout title={t('auth.reset_title')}>{children}</AuthLayout>;
}

export default ForgotPassword;
