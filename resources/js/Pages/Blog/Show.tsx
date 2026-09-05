import { Head, Link } from '@inertiajs/react';
import { useTranslation } from '../../hooks/useTranslation';
import { Container } from '../../Components/ui';
import type { BlogArticle } from '../../types';

export default function BlogShow({ article }: { article: BlogArticle }) {
  const { t } = useTranslation();

  return (
    <>
      <Head title={`${article.title} — Blog`}>
        <meta name="description" content={article.excerpt} />
      </Head>

      <article className="py-16">
        <Container className="max-w-3xl">
          <Link href="/blog" className="text-sm font-semibold text-signal-deep dark:text-signal">← {t('blog.back_to_list')}</Link>
          <time className="mt-6 block text-xs font-semibold uppercase tracking-wide text-ink-fade">
            {new Date(article.date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })}
          </time>
          <h1 className="mt-2 font-display text-3xl font-bold text-ink sm:text-4xl">{article.title}</h1>
          {article.image && <img src={article.image} alt="" className="mt-8 w-full rounded-2xl object-cover" />}
          <div
            className="prose prose-slate mt-8 max-w-none prose-headings:font-display prose-headings:text-ink prose-p:text-ink-soft prose-a:text-signal-deep dark:prose-invert"
            dangerouslySetInnerHTML={{ __html: article.content ?? '' }}
          />
        </Container>
      </article>
    </>
  );
}
