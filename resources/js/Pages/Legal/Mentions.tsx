import { Head } from '@inertiajs/react';
import { useTranslation } from '../../hooks/useTranslation';
import LegalLayout from '../../Layouts/LegalLayout';

export default function Mentions() {
  const { t } = useTranslation();
  return (
    <>
      <Head title={t('legal_mentions.meta_title')}><meta name="description" content={t('legal_mentions.meta_description')} /></Head>
      <LegalLayout title={t('legal_mentions.title')}>
        <h2>{t('legal_mentions.h1')}</h2>
        <table>
          <tbody>
            <tr><td>{t('legal_mentions.row_denomination')}</td><td>SlapIA</td></tr>
            <tr><td>{t('legal_mentions.row_forme')}</td><td>{t('legal_mentions.val_forme')}</td></tr>
            <tr><td>{t('legal_mentions.row_ape')}</td><td>{t('legal_mentions.val_ape')}</td></tr>
            <tr><td>{t('legal_mentions.row_tva')}</td><td>{t('legal_mentions.val_tva')}</td></tr>
            <tr><td>{t('legal_mentions.row_directeur')}</td><td>Thomas Lapierre</td></tr>
            <tr><td>{t('legal_mentions.row_contact')}</td><td>contact@slapia.com</td></tr>
          </tbody>
        </table>
        <p>{t('legal_mentions.dev_note')}</p>
        <h2>{t('legal_mentions.h2')}</h2>
        <p>{t('legal_mentions.infra_note')}</p>
        <h2>{t('legal_mentions.h3')}</h2>
        <p>{t('legal_mentions.h3_text')}</p>
        <h2>{t('legal_mentions.h4')}</h2>
        <p>{t('legal_mentions.h4_text')}</p>
        <h2>{t('legal_mentions.h5')}</h2>
        <p>
          {t('legal_mentions.h5_text_pre')} <a href="/confidentialite">{t('legal_mentions.h5_link1')}</a>
          {t('legal_mentions.h5_text_mid')} <a href="/cgv">{t('legal_mentions.h5_link2')}</a>.
        </p>
      </LegalLayout>
    </>
  );
}
