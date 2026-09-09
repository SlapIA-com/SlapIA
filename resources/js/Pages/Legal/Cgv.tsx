import { Head } from '@inertiajs/react';
import { useTranslation } from '../../hooks/useTranslation';
import LegalLayout from '../../Layouts/LegalLayout';

export default function Cgv() {
  const { t } = useTranslation();
  return (
    <>
      <Head title={t('legal_cgv.meta_title')}><meta name="description" content={t('legal_cgv.meta_description')} /></Head>
      <LegalLayout title={t('legal_cgv.title')}>
        <h2>{t('legal_cgv.h1')}</h2><p>{t('legal_cgv.h1_text')}</p>
        <h2>{t('legal_cgv.h2')}</h2><p>{t('legal_cgv.h2_text')}</p>
        <h2>{t('legal_cgv.h3')}</h2><p>{t('legal_cgv.h3_text')}</p>
        <h2>{t('legal_cgv.h4')}</h2><p>{t('legal_cgv.h4_text')}</p>
        <h2>{t('legal_cgv.h5')}</h2><p>{t('legal_cgv.h5_text')}</p>
        <h2>{t('legal_cgv.h6')}</h2><p>{t('legal_cgv.h6_text')}</p>
        <h2>{t('legal_cgv.h7')}</h2><p>{t('legal_cgv.h7_text_pre')} contact@slapia.com. {t('legal_cgv.h7_text_post')}</p>
      </LegalLayout>
    </>
  );
}
