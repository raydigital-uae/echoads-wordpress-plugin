import { useMemo } from 'react';
import { TRANSLATIONS } from '../constants/translations';
import type { Translations } from '../constants/translations';

export const useTranslations = (langCode: string): Translations => {
  return useMemo(() => {
    const code = langCode.toLowerCase();
    return TRANSLATIONS[code] || TRANSLATIONS.en;
  }, [langCode]);
};
