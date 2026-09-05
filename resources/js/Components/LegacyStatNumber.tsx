import { useEffect, useRef, useState } from 'react';

/**
 * Port de statNumHtml() (includes/stats.php) + initCountStats() (main.js) :
 * même markup ".stat__num" et même animation de comptage 0 → valeur au
 * scroll (repli statique si la valeur n'est pas connue, ex. hors-ligne DB).
 */
export default function LegacyStatNumber({
  value,
  decimals = 0,
  suffix = '',
  fallback,
  decimalSep = ',',
}: {
  value: number | null;
  decimals?: number;
  suffix?: string;
  fallback: string;
  decimalSep?: string;
}) {
  const ref = useRef<HTMLDivElement>(null);
  const finalDisplay = value !== null ? value.toFixed(decimals).replace('.', decimalSep) + suffix : fallback;
  const [display, setDisplay] = useState(finalDisplay);

  useEffect(() => {
    const el = ref.current;
    if (!el || value === null) return;

    const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduceMotion) {
      setDisplay(finalDisplay);
      return;
    }

    const observer = new IntersectionObserver(
      ([entry]) => {
        if (!entry.isIntersecting) return;
        observer.disconnect();
        const duration = 1100;
        let start: number | null = null;
        let raf = 0;
        function frame(ts: number) {
          if (start === null) start = ts;
          const progress = Math.min((ts - start) / duration, 1);
          const eased = 1 - Math.pow(1 - progress, 3);
          const current = value! * eased;
          setDisplay(current.toFixed(decimals).replace('.', decimalSep) + suffix);
          if (progress < 1) {
            raf = requestAnimationFrame(frame);
          } else {
            setDisplay(finalDisplay);
          }
        }
        raf = requestAnimationFrame(frame);
        return () => cancelAnimationFrame(raf);
      },
      { threshold: 0.4 }
    );
    observer.observe(el);
    return () => observer.disconnect();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [value]);

  return (
    <div className="stat__num" ref={ref}>
      {display}
    </div>
  );
}
