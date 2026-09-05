import './bootstrap';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import SiteLayout from './Layouts/SiteLayout';

const pages = import.meta.glob('./Pages/**/*.tsx', { eager: true });

createInertiaApp({
  resolve: (name) => {
    const page: any = pages[`./Pages/${name}.tsx`];
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
