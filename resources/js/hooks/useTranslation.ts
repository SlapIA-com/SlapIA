import { usePage } from '@inertiajs/react';
import type { SharedProps } from '../types';

/**
 * Équivalent du t() PHP de l'ancien includes/i18n.php : accès par clé
 * pointée ('home.hero_title'), avec repli sur la clé brute si absente —
 * même comportement que l'ancien site pendant que EN/DE se complètent.
 */
export function useTranslation() {
  const { translations, locale } = usePage<SharedProps>().props;

  function t(key: string): any {
    const parts = key.split('.');
    let value: any = translations;
    for (const part of parts) {
      if (value === null || typeof value !== 'object' || !(part in value)) {
        return key;
      }
      value = value[part];
    }
    return value;
  }

  return { t, locale };
}
