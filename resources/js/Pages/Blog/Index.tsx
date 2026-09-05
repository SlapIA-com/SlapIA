import { Head, Link } from '@inertiajs/react';
import { useTranslation } from '../../hooks/useTranslation';
import { Container, Eyebrow } from '../../Components/ui';
import Reveal from '../../Components/Reveal';
import type { BlogArticle } from '../../types';

export default function BlogIndex({ articles }: { articles: BlogArticle[] }) {
  const { t } = useTranslation();

  return (
    <>
      <Head title={t('blog.meta_title')}>
        <meta name="description" content={t('blog.meta_description')} />
      </Head>

      <section className="border-b border-line py-20">
        <Container>
          <Eyebrow>{t('blog.eyebrow')}</Eyebrow>
          <h1 className="mt-4 font-display text-4xl font-bold text-ink sm:text-5xl">
            {t('blog.title_pre')}<mark className="rounded bg-signal/20 px-1 text-signal-deep dark:text-signal">{t('blog.title_mark')}</mark>
          </h1>
          <p className="mt-6 max-w-xl text-ink-fade">{t('blog.lede')}</p>
        </Container>
      </section>

      <section className="py-16">
        <Container>
          {articles.length === 0 ? (
            <p className="text-ink-fade">{t('blog.empty')}</p>
          ) : (
            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
              {articles.map((article, i) => (
                <Reveal key={article.id} delay={(i % 6) * 60}>
                  <Link href={`/blog/${article.slug}`} className="flex h-full flex-col overflow-hidden rounded-2xl border border-line bg-paper hover:shadow-lg">
                    {article.image && <img src={article.image} alt="" className="h-44 w-full object-cover" />}
                    <div className="flex flex-1 flex-col p-5">
                      <time className="text-xs font-semibold uppercase tracking-wide text-ink-fade">
                        {new Date(article.date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })}
                      </time>
                      <h3 className="mt-2 font-display text-base font-semibold text-ink">{article.title}</h3>
                      <p className="mt-2 flex-1 text-sm text-ink-fade line-clamp-3">{article.excerpt}</p>
                    </div>
                  </Link>
                </Reveal>
              ))}
            </div>
          )}
        </Container>
      </section>
    </>
  );
}
