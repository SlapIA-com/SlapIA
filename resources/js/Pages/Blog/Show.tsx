import { Head, Link } from '@inertiajs/react';
import { useTranslation } from '../../hooks/useTranslation';
import type { BlogArticle } from '../../types';

export default function BlogShow({ article }: { article: BlogArticle }) {
  const { t } = useTranslation();

  return (
    <>
      <Head title={`${article.title} — Blog`}>
        <meta name="description" content={article.excerpt} />
      </Head>

      <section className="page-hero">
        <div className="container">
          <Link href="/blog" className="blog-article__back-link">← {t('blog.back_to_list')}</Link>
          <span className="eyebrow">
            {new Date(article.date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })}
          </span>
          <h1 className="page-hero__title">{article.title}</h1>
        </div>
      </section>

      <section className="section" style={{ paddingTop: 0 }}>
        <div className="container blog-article-layout">
          {article.image && (
            <div className="blog-article__cover">
              <img src={article.image} alt="" loading="lazy" />
            </div>
          )}
          <div className="blog-article__content" dangerouslySetInnerHTML={{ __html: article.content ?? '' }} />
          <Link href="/blog" className="btn btn--ghost blog-article__back">← {t('blog.back_to_list')}</Link>
        </div>
      </section>
    </>
  );
}
