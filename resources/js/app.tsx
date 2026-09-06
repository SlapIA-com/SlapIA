import './bootstrap';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import SiteLayout from './Layouts/SiteLayout';

// Sans { eager: true } : chaque page devient un chunk JS séparé, chargé à
// la demande plutôt que tout regroupé dans un seul bundle. Avant ce
// changement, un visiteur anonyme de la page d'accueil téléchargeait aussi
// le code de l'admin (Chart.js compris), du dashboard client et du blog —
// 533 Ko de JS pour une simple visite de la home. `resolve` peut renvoyer
// une Promise (supporté nativement par Inertia), donc pas besoin d'un
// helper externe pour ça.
const pages = import.meta.glob('./Pages/**/*.tsx');

createInertiaApp({
  resolve: async (name) => {
    const page: any = await pages[`./Pages/${name}.tsx`]();
    // Layout par défaut : SiteLayout. Les pages Auth/Dashboard/Admin
    // définissent leur propre `Page.layout` pour l'écraser.
    page.default.layout = page.default.layout ?? ((p: any) => <SiteLayout>{p}</SiteLayout>);
    return page;
  },
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />);
  },
  progress: {
    color: '#B36FE0',
  },
});
