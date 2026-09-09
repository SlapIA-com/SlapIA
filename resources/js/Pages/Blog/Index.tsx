import { Head, Link } from '@inertiajs/react';
import { useTranslation } from '../../hooks/useTranslation';
import type { BlogArticle } from '../../types';

export default function BlogIndex({ articles }: { articles: BlogArticle[] }) {
  const { t } = useTranslation();

  return (
    <>
      <Head title={t('blog.meta_title')}>
        <meta name="description" content={t('blog.meta_description')} />
      </Head>

      <section className="page-hero">
        <div className="page-hero-canvas" aria-hidden="true">
          <span></span><span></span><span></span><span></span><span></span><span></span>
        </div>
        <div className="container">
          <span className="eyebrow">{t('blog.eyebrow')}</span>
          <h1 className="page-hero__title">
            {t('blog.title_pre')}<mark>{t('blog.title_mark')}</mark>
          </h1>
          <p className="page-hero__lede">{t('blog.lede')}</p>
        </div>
      </section>

      <section className="section" style={{ paddingTop: 0 }}>
        <div className="container">
          {articles.length === 0 ? (
            <p className="blog-empty">{t('blog.empty')}</p>
          ) : (
            <div className="blog-grid">
              {articles.map((article) => (
                <BlogCard key={article.id} article={article} />
              ))}
            </div>
          )}
        </div>
      </section>
    </>
  );
}

// Pas de useReveal ici : Inertia <Link> ne garantit pas de forwarder sa ref
// vers l'ancre — carte affichée directement (sans l'animation d'apparition
// au scroll) plutôt que de risquer de rester bloquée à opacity:0, même
// précédent que CourseCard dans Home.tsx.
function BlogCard({ article }: { article: BlogArticle }) {
  return (
    <Link href={`/blog/${article.slug}`} className="blog-card reveal is-visible">
      {article.image && (
        <div className="blog-card__image">
          <img src={article.image} alt="" loading="lazy" />
        </div>
      )}
      <div className="blog-card__body">
        <span className="blog-card__date">
          {new Date(article.date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })}
        </span>
        <h2 className="blog-card__title">{article.title}</h2>
        <p className="blog-card__excerpt">{article.excerpt}</p>
      </div>
    </Link>
  );
}
