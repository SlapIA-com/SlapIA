import './bootstrap';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import SiteLayout from './Layouts/SiteLayout';
import ChatWidget from './Components/ChatWidget';

// ChatWidget est un composant React maison de quelques Ko (voir son
// fichier) : contrairement au widget officiel @n8n/chat (Vue + son
// écosystème, ~250 Ko), pas besoin de le charger en import() différé, son
// poids est négligeable dans le chunk "app" commun à toutes les pages.

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
    // Chat IA (bas droite) : partagé sur toutes les pages (voir
    // HandleInertiaRequests::share) et monté une seule fois ici, hors de
    // l'arbre de pages Inertia, pour survivre aux navigations entre pages.
    const webhookUrl = props.initialPage.props.n8nChatWebhookUrl as string | null;
    createRoot(el).render(
      <>
        <App {...props} />
        {webhookUrl && <ChatWidget webhookUrl={webhookUrl} />}
      </>
    );
  },
  // Désactivé : la barre de chargement par défaut d'Inertia (+ son spinner
  // fixe en haut à droite) reste bloquée affichée en permanence sur iOS
  // (Safari et Edge, jamais reproduit sur desktop où le chargement est trop
  // rapide pour la voir) — visible comme un contour/rond parasite autour du
  // header dès l'ouverture du site, sur toutes les pages. Le site a déjà sa
  // propre jauge de progression (.rail / .progress-mobile, progression de
  // lecture), donc pas de perte fonctionnelle à l'éteindre.
  progress: false,
});
