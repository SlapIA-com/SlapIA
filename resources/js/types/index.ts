export interface AuthUser {
  id: number;
  email: string;
  name: string;
  role: 'admin' | 'entreprise' | 'particulier';
}

/** Dictionnaire de traductions — même forme imbriquée que lang/fr/messages.php. */
export type Translations = Record<string, any>;

export interface SharedProps {
  auth: { user: AuthUser | null };
  locale: 'fr' | 'en' | 'de';
  translations: Translations;
  flash: { success: string | null; error: string | null };
  [key: string]: any;
}

export interface Level {
  num: string;
  anchor: string;
  title: string;
  teaser: string;
  tools: string[];
  detail_title: string;
  detail_subtitle: string;
  modules: Array<{ code: string; theme: string; desc: string; tools: string[] }>;
}

export interface PublicReview {
  prenom: string;
  nom: string;
  profession: string;
  avis: string;
  note: number | null;
  client_id: number | null;
  status: string;
  entreprise: string;
  linkedin: string;
}

export interface Stats {
  entreprises: number;
  particuliers: number;
  satisfaction: number | null;
  satisfaction_count: number;
  is_live: boolean;
}

export interface BlogArticle {
  id: string;
  title: string;
  excerpt: string;
  date: string;
  image: string | null;
  slug: string;
  content?: string;
}
