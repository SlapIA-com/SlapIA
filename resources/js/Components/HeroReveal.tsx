import { useEffect, useState, type CSSProperties, type ElementType, type ReactNode } from 'react';

/** Port de initHeroReveal() (main.js) : ajoute .is-in au prochain frame (entrée du hero, pas liée au scroll). */
export default function HeroReveal({
  children,
  delay = '0s',
  className = '',
  as: As = 'div',
}: {
  children: ReactNode;
  delay?: string;
  className?: string;
  as?: ElementType;
}) {
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    const raf1 = requestAnimationFrame(() => {
      requestAnimationFrame(() => setVisible(true));
    });
    return () => cancelAnimationFrame(raf1);
  }, []);

  const style: CSSProperties = { transitionDelay: delay };

  return (
    <As className={`hero-reveal ${visible ? 'is-in' : ''} ${className}`} style={style}>
      {children}
    </As>
  );
}
