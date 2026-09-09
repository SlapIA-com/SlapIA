import { useEffect, useRef } from 'react';

/**
 * Port direct de l'ancien assets/js/hero-canvas.js : construit le même SVG
 * (nœuds + arêtes + pastilles animées suggérant un flux d'automatisation)
 * et rejoue la même animation d'entrée + parallax souris. Mêmes classes
 * CSS (.hero-canvas__*) donc le rendu visuel est identique à l'ancien site.
 */
export default function HeroCanvas() {
  const containerRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const container = containerRef.current;
    if (!container) return;

    const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const nodes = [
      { x: 60, y: 420 },
      { x: 180, y: 280 },
      { x: 340, y: 460 },
      { x: 420, y: 180 },
      { x: 560, y: 320 },
      { x: 640, y: 120 },
      { x: 760, y: 400 },
      { x: 860, y: 220 },
      { x: 940, y: 340 },
    ];
    const edges: Array<[number, number]> = [
      [0, 1], [1, 3], [1, 2], [3, 4], [2, 4],
      [4, 5], [4, 6], [5, 7], [6, 7], [7, 8],
    ];
    const colors = ['var(--signal)', 'var(--signal-pink)', 'var(--forest)'];

    const svgNS = 'http://www.w3.org/2000/svg';
    const svg = document.createElementNS(svgNS, 'svg');
    svg.setAttribute('viewBox', '0 0 1000 560');
    svg.setAttribute('preserveAspectRatio', 'xMidYMid slice');
    svg.classList.add('hero-canvas__svg');

    const group = document.createElementNS(svgNS, 'g');
    group.classList.add('hero-canvas__group');

    edges.forEach((edge, i) => {
      const a = nodes[edge[0]];
      const b = nodes[edge[1]];
      const path = document.createElementNS(svgNS, 'path');
      const d = `M ${a.x} ${a.y} L ${b.x} ${b.y}`;
      path.setAttribute('d', d);
      path.setAttribute('id', `hero-edge-${i}`);
      path.classList.add('hero-canvas__edge');
      const length = Math.hypot(b.x - a.x, b.y - a.y);
      path.style.strokeDasharray = String(length);
      path.style.strokeDashoffset = String(reduceMotion ? 0 : length);
      path.style.transitionDelay = `${0.5 + i * 0.08}s`;
      path.setAttribute('stroke', colors[i % colors.length]);
      group.appendChild(path);

      if (!reduceMotion) {
        const dot = document.createElementNS(svgNS, 'circle');
        dot.setAttribute('r', '3.2');
        dot.classList.add('hero-canvas__pulse-dot');
        dot.setAttribute('fill', colors[i % colors.length]);
        const anim = document.createElementNS(svgNS, 'animateMotion');
        anim.setAttribute('dur', `${3 + (i % 3)}s`);
        anim.setAttribute('begin', `${2 + i * 0.3}s`);
        anim.setAttribute('repeatCount', 'indefinite');
        const mpath = document.createElementNS(svgNS, 'mpath');
        mpath.setAttributeNS('http://www.w3.org/1999/xlink', 'href', `#hero-edge-${i}`);
        anim.appendChild(mpath);
        dot.appendChild(anim);
        group.appendChild(dot);
      }
    });

    nodes.forEach((n, i) => {
      const circle = document.createElementNS(svgNS, 'circle');
      circle.setAttribute('cx', String(n.x));
      circle.setAttribute('cy', String(n.y));
      circle.setAttribute('r', i === 1 || i === 4 || i === 7 ? '9' : '6');
      circle.setAttribute('fill', colors[i % colors.length]);
      circle.classList.add('hero-canvas__node');
      circle.style.animationDelay = `${2.4 + i * 0.35}s`;
      circle.style.opacity = String(reduceMotion ? 1 : 0);
      circle.style.transitionDelay = `${0.2 + i * 0.08}s`;
      group.appendChild(circle);
    });

    svg.appendChild(group);
    container.appendChild(svg);

    function revealAll() {
      if (!container) return;
      container.classList.add('is-in');
      group.querySelectorAll<SVGPathElement>('.hero-canvas__edge').forEach((path) => {
        path.style.strokeDashoffset = '0';
      });
      group.querySelectorAll<SVGCircleElement>('.hero-canvas__node').forEach((circle) => {
        circle.style.opacity = '1';
      });
    }

    let raf1 = 0;
    let raf2 = 0;
    if (!reduceMotion) {
      raf1 = requestAnimationFrame(() => {
        raf2 = requestAnimationFrame(revealAll);
      });
    } else {
      revealAll();
    }

    let onMouseMove: ((e: MouseEvent) => void) | null = null;
    let hero: HTMLElement | null = null;
    let raf3 = 0;
    if (!reduceMotion && window.matchMedia('(hover: hover)').matches) {
      hero = container.closest('.hero') as HTMLElement | null;
      if (hero) {
        onMouseMove = (e: MouseEvent) => {
          if (raf3) return;
          raf3 = requestAnimationFrame(() => {
            const rect = hero!.getBoundingClientRect();
            const px = (e.clientX - rect.left) / rect.width - 0.5;
            const py = (e.clientY - rect.top) / rect.height - 0.5;
            group.style.transform = `translate(${px * -14}px, ${py * -10}px)`;
            raf3 = 0;
          });
        };
        hero.addEventListener('mousemove', onMouseMove);
      }
    }

    return () => {
      cancelAnimationFrame(raf1);
      cancelAnimationFrame(raf2);
      cancelAnimationFrame(raf3);
      if (hero && onMouseMove) hero.removeEventListener('mousemove', onMouseMove);
      container.innerHTML = '';
    };
  }, []);

  return <div className="hero-canvas" aria-hidden="true" ref={containerRef} />;
}
