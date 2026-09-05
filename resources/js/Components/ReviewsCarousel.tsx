import { forwardRef, useEffect, useImperativeHandle, useRef, useState } from 'react';
import type { PublicReview } from '../types';

export interface ReviewsCarouselHandle {
  prev: () => void;
  next: () => void;
}

/**
 * Port simplifié de initReviewsCarousel() (main.js) : mêmes classes CSS
 * (.reviews-marquee/.reviews-track/.review-item) et même défilement par
 * boutons + autoplay, mais sans le bouclage infini par clones DOM de
 * l'original (moins de code, jouable à l'identique visuellement — la seule
 * différence est qu'on revient au début plutôt que de boucler en douceur).
 * Les boutons prev/next vivent dans le .section-head (voir Home.tsx) donc
 * ce composant expose prev()/next() via ref plutôt que de les rendre lui-même.
 */
const ReviewsCarousel = forwardRef<ReviewsCarouselHandle, { reviews: PublicReview[] }>(function ReviewsCarousel({ reviews }, ref) {
  const trackRef = useRef<HTMLDivElement>(null);
  const containerRef = useRef<HTMLDivElement>(null);
  const [index, setIndex] = useState(0);
  const autoplayRef = useRef<number | null>(null);

  function stepWidth() {
    const first = trackRef.current?.querySelector<HTMLElement>('.review-item');
    if (!first) return 344;
    const gap = parseFloat(getComputedStyle(trackRef.current!).columnGap || getComputedStyle(trackRef.current!).gap || '0') || 0;
    return first.getBoundingClientRect().width + gap;
  }

  function move(direction: 'next' | 'prev') {
    setIndex((i) => {
      const max = reviews.length - 1;
      if (direction === 'next') return i >= max ? 0 : i + 1;
      return i <= 0 ? max : i - 1;
    });
  }

  function startAutoplay() {
    stopAutoplay();
    autoplayRef.current = window.setInterval(() => move('next'), 3000);
  }
  function stopAutoplay() {
    if (autoplayRef.current) window.clearInterval(autoplayRef.current);
  }

  useImperativeHandle(ref, () => ({
    prev: () => {
      stopAutoplay();
      move('prev');
      startAutoplay();
    },
    next: () => {
      stopAutoplay();
      move('next');
      startAutoplay();
    },
  }));

  useEffect(() => {
    startAutoplay();
    return stopAutoplay;
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [reviews.length]);

  useEffect(() => {
    function onResize() {
      if (trackRef.current) trackRef.current.style.transform = `translateX(${-(index * stepWidth())}px)`;
    }
    onResize();
    window.addEventListener('resize', onResize);
    return () => window.removeEventListener('resize', onResize);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [index]);

  if (reviews.length === 0) return null;

  return (
    <div
      className="reviews-marquee"
      ref={containerRef}
      onMouseEnter={stopAutoplay}
      onMouseLeave={startAutoplay}
    >
      <div className="reviews-inner">
        <div className="reviews-track" ref={trackRef} style={{ transition: 'transform 0.5s cubic-bezier(0.2, 0.8, 0.2, 1)' }}>
          {reviews.map((r, i) => {
            const name = `${r.prenom} ${r.nom}`.trim();
            const initials = ((r.prenom[0] ?? '') + (r.nom[0] ?? '')).toUpperCase();
            const filled = r.note !== null ? Math.max(0, Math.min(5, Math.round(r.note))) : 0;
            return (
              <div className="review-item" key={i}>
                <div className="review-header">
                  <div className="review-avatar">
                    {r.client_id !== null ? (
                      <img
                        src={`/api/avatar/${r.client_id}`}
                        alt=""
                        loading="lazy"
                        onError={(e) => {
                          e.currentTarget.style.display = 'none';
                          const sibling = e.currentTarget.nextElementSibling as HTMLElement | null;
                          if (sibling) sibling.style.display = 'flex';
                        }}
                      />
                    ) : null}
                    <span style={{ display: r.client_id !== null ? 'none' : 'flex' }}>{initials}</span>
                  </div>
                  <div className="review-info">
                    {r.linkedin ? (
                      <a href={r.linkedin} target="_blank" rel="noopener noreferrer" className="review-name">
                        {name}
                      </a>
                    ) : (
                      <span className="review-name">{name}</span>
                    )}
                    {r.profession && (
                      <div className="review-profession">
                        {r.profession}
                        {r.status === 'Entreprise' && r.entreprise ? <span className="company-name"> · {r.entreprise}</span> : null}
                      </div>
                    )}
                  </div>
                </div>
                <div className="review-content-scroll">
                  <p className="review-text">{r.avis}</p>
                </div>
                <div className="review-stars">{'★'.repeat(filled)}{'☆'.repeat(5 - filled)}</div>
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
});

export default ReviewsCarousel;

export function ReviewsNav({ onPrev, onNext }: { onPrev: () => void; onNext: () => void }) {
  return (
    <div className="reviews-nav">
      <button className="nav-btn" aria-label="Précédent" type="button" onClick={onPrev}>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round">
          <path d="M15 18l-6-6 6-6" />
        </svg>
      </button>
      <button className="nav-btn" aria-label="Suivant" type="button" onClick={onNext}>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round">
          <path d="M9 18l6-6-6-6" />
        </svg>
      </button>
    </div>
  );
}
