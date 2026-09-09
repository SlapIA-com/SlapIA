import { useEffect, useRef, useState } from 'react';

/**
 * Équivalent de la classe .reveal + IntersectionObserver de l'ancien
 * assets/js/main.js, mais appliqué directement sur l'élément réel (carte,
 * lien...) plutôt que via un <div> englobant — nécessaire pour ne pas casser
 * la mise en page CSS grid/flex (.grid-3, .course-card, etc. doivent rester
 * l'élément grid/flex direct, pas un wrapper).
 */
export function useReveal<T extends HTMLElement = HTMLDivElement>(delay = 0) {
  const ref = useRef<T>(null);
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    const el = ref.current;
    if (!el || !('IntersectionObserver' in window)) {
      // Repli sûr : si la ref ne s'est pas attachée (ex. composant qui ne la
      // transmet pas au DOM) ou si l'API est absente, on affiche directement
      // plutôt que de rester bloqué à opacity:0 pour toujours.
      setVisible(true);
      return;
    }
    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          setVisible(true);
          observer.disconnect();
        }
      },
      { threshold: 0.15, rootMargin: '0px 0px -40px 0px' }
    );
    observer.observe(el);
    return () => observer.disconnect();
  }, []);

  return {
    ref,
    className: `reveal${visible ? ' is-visible' : ''}`,
    style: { transitionDelay: `${delay}s` },
  };
}
