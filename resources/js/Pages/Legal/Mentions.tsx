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
        <table className="legal-table">
          <tbody>
            <tr><th>{t('legal_mentions.row_denomination')}</th><td>SlapIA</td></tr>
            <tr><th>{t('legal_mentions.row_forme')}</th><td>{t('legal_mentions.val_forme')}</td></tr>
            <tr><th>{t('legal_mentions.row_siren')}</th><td>100 946 722</td></tr>
            <tr><th>{t('legal_mentions.row_siret')}</th><td>100 946 722 00012</td></tr>
            <tr><th>{t('legal_mentions.row_ape')}</th><td>{t('legal_mentions.val_ape')}</td></tr>
            <tr><th>{t('legal_mentions.row_tva')}</th><td>{t('legal_mentions.val_tva')}</td></tr>
            <tr><th>{t('legal_mentions.row_directeur')}</th><td>Thomas Lapierre</td></tr>
            <tr><th>{t('legal_mentions.row_contact')}</th><td><a href="mailto:contact@slapia.com">contact@slapia.com</a></td></tr>
          </tbody>
        </table>
        <p>{t('legal_mentions.dev_note')}</p>

        <h2>{t('legal_mentions.h2')}</h2>
        <table className="legal-table">
          <tbody>
            <tr><th>{t('legal_mentions.row_hebergeur')}</th><td>GNL Solution</td></tr>
            <tr><th>{t('legal_mentions.row_adresse')}</th><td>20 rue Gustave Courbet, 25000 Besançon</td></tr>
            <tr><th>{t('legal_mentions.row_contact')}</th><td><a href="mailto:contact@gnl-solution.fr">contact@gnl-solution.fr</a></td></tr>
            <tr><th>{t('legal_mentions.row_telephone')}</th><td>03 65 67 01 69</td></tr>
          </tbody>
        </table>
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
