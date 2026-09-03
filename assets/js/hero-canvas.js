(function () {
  var container = document.querySelector('.hero-canvas');
  if (!container) return;

  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Hand-placed nodes suggesting a left-to-right automation flow.
  var nodes = [
    { x: 60,  y: 420 },
    { x: 180, y: 280 },
    { x: 340, y: 460 },
    { x: 420, y: 180 },
    { x: 560, y: 320 },
    { x: 640, y: 120 },
    { x: 760, y: 400 },
    { x: 860, y: 220 },
    { x: 940, y: 340 }
  ];
  var edges = [
    [0, 1], [1, 3], [1, 2], [3, 4], [2, 4],
    [4, 5], [4, 6], [5, 7], [6, 7], [7, 8]
  ];
  var colors = ['var(--signal)', 'var(--signal-pink)', 'var(--forest)'];

  var svgNS = 'http://www.w3.org/2000/svg';
  var svg = document.createElementNS(svgNS, 'svg');
  svg.setAttribute('viewBox', '0 0 1000 560');
  svg.setAttribute('preserveAspectRatio', 'xMidYMid slice');
  svg.classList.add('hero-canvas__svg');

  var group = document.createElementNS(svgNS, 'g');
  group.classList.add('hero-canvas__group');

  edges.forEach(function (edge, i) {
    var a = nodes[edge[0]];
    var b = nodes[edge[1]];
    var path = document.createElementNS(svgNS, 'path');
    var d = 'M ' + a.x + ' ' + a.y + ' L ' + b.x + ' ' + b.y;
    path.setAttribute('d', d);
    path.setAttribute('id', 'hero-edge-' + i);
    path.classList.add('hero-canvas__edge');
    var length = Math.hypot(b.x - a.x, b.y - a.y);
    path.style.strokeDasharray = length;
    path.style.strokeDashoffset = reduceMotion ? 0 : length;
    path.style.transitionDelay = (0.5 + i * 0.08) + 's';
    path.setAttribute('stroke', colors[i % colors.length]);
    group.appendChild(path);

    if (!reduceMotion) {
      var dot = document.createElementNS(svgNS, 'circle');
      dot.setAttribute('r', 3.2);
      dot.classList.add('hero-canvas__pulse-dot');
      dot.setAttribute('fill', colors[i % colors.length]);
      var anim = document.createElementNS(svgNS, 'animateMotion');
      anim.setAttribute('dur', (3 + (i % 3)) + 's');
      anim.setAttribute('begin', (2 + i * 0.3) + 's');
      anim.setAttribute('repeatCount', 'indefinite');
      var mpath = document.createElementNS(svgNS, 'mpath');
      mpath.setAttributeNS('http://www.w3.org/1999/xlink', 'href', '#hero-edge-' + i);
      anim.appendChild(mpath);
      dot.appendChild(anim);
      group.appendChild(dot);
    }
  });

  nodes.forEach(function (n, i) {
    var circle = document.createElementNS(svgNS, 'circle');
    circle.setAttribute('cx', n.x);
    circle.setAttribute('cy', n.y);
    circle.setAttribute('r', i === 1 || i === 4 || i === 7 ? 9 : 6);
    circle.setAttribute('fill', colors[i % colors.length]);
    circle.classList.add('hero-canvas__node');
    circle.style.animationDelay = (2.4 + i * 0.35) + 's';
    circle.style.opacity = reduceMotion ? 1 : 0;
    circle.style.transitionDelay = (0.2 + i * 0.08) + 's';
    group.appendChild(circle);
  });

  svg.appendChild(group);
  container.appendChild(svg);

  // Trigger the entrance transition on the next frame.
  function revealAll() {
    container.classList.add('is-in');
    group.querySelectorAll('.hero-canvas__edge').forEach(function (path) {
      path.style.strokeDashoffset = 0;
    });
    group.querySelectorAll('.hero-canvas__node').forEach(function (circle) {
      circle.style.opacity = 1;
    });
  }

  if (!reduceMotion) {
    requestAnimationFrame(function () {
      requestAnimationFrame(revealAll);
    });
  } else {
    revealAll();
  }

  // Subtle cursor parallax — desktop only, skipped under reduced motion.
  if (!reduceMotion && window.matchMedia('(hover: hover)').matches) {
    var hero = document.querySelector('.hero');
    var raf = null;
    hero.addEventListener('mousemove', function (e) {
      if (raf) return;
      raf = requestAnimationFrame(function () {
        var rect = hero.getBoundingClientRect();
        var px = (e.clientX - rect.left) / rect.width - 0.5;
        var py = (e.clientY - rect.top) / rect.height - 0.5;
        group.style.transform = 'translate(' + (px * -14) + 'px, ' + (py * -10) + 'px)';
        raf = null;
      });
    });
  }
})();
