export default {
  plugins: {
    // Doit tourner AVANT tailwindcss : sans lui, Tailwind ne résout pas les
    // @import de fichiers locaux (nos legacy/*.css) et les fait juste
    // disparaître silencieusement du CSS final (pas d'erreur de build, mais
    // le style de l'ancien site ne se retrouvait jamais dans le bundle).
    'postcss-import': {},
    tailwindcss: {},
    autoprefixer: {},
  },
};
