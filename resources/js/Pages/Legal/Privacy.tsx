import { Head } from '@inertiajs/react';
import { useTranslation } from '../../hooks/useTranslation';
import LegalLayout from '../../Layouts/LegalLayout';

export default function Privacy() {
  const { t } = useTranslation();
  return (
    <>
      <Head title={t('legal_privacy.meta_title')}><meta name="description" content={t('legal_privacy.meta_description')} /></Head>
      <LegalLayout title={t('legal_privacy.title')} lede={t('legal_privacy.lede')}>
        <h2>{t('legal_privacy.h1')}</h2>
        <p>{t('legal_privacy.h1_text')} contact@slapia.com.</p>
        <h2>{t('legal_privacy.h2')}</h2>
        <p>{t('legal_privacy.h2_text')}</p>
        <ul>
          <li>{t('legal_privacy.h2_li1')}</li>
          <li>{t('legal_privacy.h2_li2')}</li>
          <li>{t('legal_privacy.h2_li3')}</li>
          <li>{t('legal_privacy.h2_li4')}</li>
        </ul>
        <p>{t('legal_privacy.h2_text2')}</p>
        <h2>{t('legal_privacy.h3')}</h2>
        <p>{t('legal_privacy.h3_text')}</p>
        <ul>
          <li>{t('legal_privacy.h3_li1')}</li>
          <li>{t('legal_privacy.h3_li2')}</li>
          <li>{t('legal_privacy.h3_li3')}</li>
          <li>{t('legal_privacy.h3_li4')}</li>
        </ul>
        <p>{t('legal_privacy.h3_text2')}</p>
        <h2>{t('legal_privacy.h4')}</h2>
        <p>{t('legal_privacy.h4_text')}</p>
        <h2>{t('legal_privacy.h5')}</h2>
        <p>{t('legal_privacy.h5_text')}</p>
        <h2>{t('legal_privacy.h6')}</h2>
        <p>{t('legal_privacy.h6_text')}</p>
        <h2>{t('legal_privacy.h7')}</h2>
        <p>{t('legal_privacy.h7_text')}</p>
        <h2>{t('legal_privacy.h8')}</h2>
        <p>{t('legal_privacy.h8_text_pre')} contact@slapia.com{t('legal_privacy.h8_text_mid')}cnil.fr).</p>
        <h2>{t('legal_privacy.h9')}</h2>
        <p>{t('legal_privacy.h9_text')}</p>
      </LegalLayout>
    </>
  );
}
