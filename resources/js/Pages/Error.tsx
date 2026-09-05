import { Head, Link } from '@inertiajs/react';
import { useTranslation } from '../hooks/useTranslation';
import { Container, Eyebrow } from '../Components/ui';

/** Rendu pour toute erreur HTTP (404 en particulier) — voir bootstrap/app.php ->withExceptions(). */
export default function Error({ status }: { status: number }) {
  const { t } = useTranslation();
  const is404 = status === 404;

  return (
    <>
      <Head title={t('error404.meta_title')}><meta name="description" content={t('error404.meta_description')} /></Head>
      <section className="flex min-h-[60vh] items-center py-20">
        <Container className="text-center">
          <Eyebrow>{is404 ? t('error404.eyebrow') : `Erreur ${status}`}</Eyebrow>
          <h1 className="mt-4 font-display text-4xl font-bold text-ink sm:text-5xl">
            {t('error404.title_pre')}<mark className="rounded bg-signal/20 px-1 text-signal-deep dark:text-signal">{t('error404.title_mark')}</mark>{t('error404.title_post')}
          </h1>
          <p className="mx-auto mt-6 max-w-md text-ink-fade">{t('error404.lede')}</p>
          <div className="mt-8 flex flex-wrap justify-center gap-3">
            <Link href="/" className="rounded-full bg-ink px-5 py-2.5 text-sm font-semibold text-white">{t('error404.link_home')}</Link>
            <Link href="/formations" className="rounded-full border border-line-strong px-5 py-2.5 text-sm font-semibold text-ink">{t('error404.link_courses')}</Link>
            <Link href="/services-pc" className="rounded-full border border-line-strong px-5 py-2.5 text-sm font-semibold text-ink">{t('error404.link_services')}</Link>
            <Link href="/contact" className="rounded-full border border-line-strong px-5 py-2.5 text-sm font-semibold text-ink">{t('error404.link_contact')}</Link>
          </div>
        </Container>
      </section>
    </>
  );
}
