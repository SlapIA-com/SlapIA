<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>
      (function () {
        try {
          var stored = localStorage.getItem('slapia-theme');
          var theme = stored || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
          document.documentElement.classList.toggle('dark', theme === 'dark');
          document.documentElement.setAttribute('data-theme', theme);
        } catch (e) {}
      })();
    </script>
    <link rel="icon" type="image/png" href="/assets/img/brand/logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    {{-- URL exacte de l'ancien assets/css/style.css (@import Google Fonts) : Bricolage Grotesque
         avec axe optique "opsz", IBM Plex Sans avec italiques, IBM Plex Mono jusqu'à 600 —
         plus complète que l'URL précédente ici, qui omettait les italiques et l'axe opsz. --}}
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400..800&family=IBM+Plex+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
