import { Head, Link } from '@inertiajs/react';
import { useTranslation } from '../hooks/useTranslation';
import { Container, Eyebrow, Tag } from '../Components/ui';
import Reveal from '../Components/Reveal';
import type { Level } from '../types';

export default function Courses() {
  const { t } = useTranslation();
  const levels: Level[] = t('levels');

  return (
    <>
      <Head title={t('courses_page.meta_title')}>
        <meta name="description" content={t('courses_page.meta_description')} />
      </Head>

      <section className="border-b border-line py-20">
        <Container>
          <Eyebrow>{t('courses_page.eyebrow')}</Eyebrow>
          <h1 className="mt-4 max-w-2xl font-display text-4xl font-bold text-ink sm:text-5xl">
            {t('courses_page.title_pre')}<mark className="rounded bg-signal/20 px-1 text-signal-deep dark:text-signal">{t('courses_page.title_mark')}</mark>{t('courses_page.title_post')}
          </h1>
          <p className="mt-6 max-w-xl text-ink-fade">{t('courses_page.lede')}</p>
        </Container>
      </section>

      <section className="py-16">
        <Container>
          <div className="grid gap-6 sm:grid-cols-3">
            {levels.map((level) => (
              <a key={level.anchor} href={`#${level.anchor}`} className="flex flex-col rounded-2xl border border-line bg-paper p-6 hover:shadow-lg">
                <Tag signal>Niveau {level.num}</Tag>
                <h3 className="mt-4 font-display text-lg font-semibold text-ink">{level.title}</h3>
                <p className="mt-2 flex-1 text-sm text-ink-fade">{level.teaser}</p>
                <div className="mt-4 flex flex-wrap gap-2">{level.tools.map((tl) => <Tag key={tl}>{tl}</Tag>)}</div>
                <span className="mt-6 text-sm font-semibold text-signal-deep dark:text-signal">{t('courses_page.levels_cta')} →</span>
              </a>
            ))}
          </div>
        </Container>
      </section>

      <section className="bg-paper py-16">
        <Container>
          {levels.map((level) => (
            <Reveal key={level.anchor} className="mb-14" >
              <div id={level.anchor} className="scroll-mt-24">
                <div className="mb-6 flex items-start gap-4">
                  <span className="font-display text-3xl font-bold text-line-strong">{level.num}</span>
                  <div>
                    <h3 className="font-display text-xl font-semibold text-ink">{level.detail_title}</h3>
                    <p className="text-sm text-ink-fade">{level.detail_subtitle}</p>
                  </div>
                </div>
                <div className="overflow-x-auto rounded-2xl border border-line bg-paper">
                  <table className="w-full min-w-[640px] text-left text-sm">
                    <thead className="border-b border-line text-xs uppercase tracking-wide text-ink-fade">
                      <tr>
                        <th className="px-4 py-3">{t('courses_page.module_label')}</th>
                        <th className="px-4 py-3">{t('courses_page.theme_label')}</th>
                        <th className="px-4 py-3">{t('courses_page.action_label')}</th>
                        <th className="px-4 py-3">{t('courses_page.tools_label')}</th>
                      </tr>
                    </thead>
                    <tbody>
                      {level.modules.map((m) => (
                        <tr key={m.code} className="border-b border-line last:border-0">
                          <td className="px-4 py-3 font-semibold text-ink">{m.code}</td>
                          <td className="px-4 py-3 text-ink-soft">{m.theme}</td>
                          <td className="px-4 py-3 text-ink-fade">{m.desc}</td>
                          <td className="px-4 py-3">
                            {m.tools.length === 0 ? (
                              <span className="text-ink-fade">{t('courses_page.no_tool')}</span>
                            ) : (
                              <div className="flex flex-wrap gap-1">{m.tools.map((tl) => <Tag key={tl}>{tl}</Tag>)}</div>
                            )}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            </Reveal>
          ))}
        </Container>
      </section>

      <section className="py-20">
        <Container>
          <p className="mb-8 text-center text-sm text-ink-fade">
            {t('courses_page.vip_title')}{' '}
            <Link href="/tarifs#mentorat" className="font-semibold text-forest underline underline-offset-2">{t('courses_page.vip_cta')} →</Link>
          </p>
          <div className="rounded-3xl bg-surface-dark p-10 text-center text-on-dark sm:p-16">
            <h2 className="font-display text-2xl font-semibold sm:text-3xl">{t('courses_page.bottom_cta_title')}</h2>
            <Link href="/contact" className="mt-6 inline-flex rounded-full bg-signal px-6 py-3 text-sm font-semibold text-on-accent">
              {t('courses_page.bottom_cta_btn')} →
            </Link>
          </div>
        </Container>
      </section>
    </>
  );
}
