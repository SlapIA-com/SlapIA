import { useEffect, useRef, useState, type ReactNode } from 'react';

/** Port de initMarkReveal() (main.js) : le <mark> ne s'anime (.is-marked) qu'une fois visible à l'écran. */
export default function RevealMark({ children }: { children: ReactNode }) {
  const ref = useRef<HTMLElement>(null);
  const [marked, setMarked] = useState(false);

  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    if (!('IntersectionObserver' in window)) {
      setMarked(true);
      return;
    }
    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          setMarked(true);
          observer.disconnect();
        }
      },
      { threshold: 0.5 }
    );
    observer.observe(el);
    return () => observer.disconnect();
  }, []);

  return (
    <mark ref={ref} className={marked ? 'is-marked' : ''}>
      {children}
    </mark>
  );
}
