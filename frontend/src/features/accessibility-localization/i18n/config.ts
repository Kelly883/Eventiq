import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import LanguageDetector from 'i18next-browser-languagedetector';

import en from './locales/en/translation.json';
import es from './locales/es/translation.json';
import fr from './locales/fr/translation.json';
import de from './locales/de/translation.json';
import pt from './locales/pt/translation.json';
import zh from './locales/zh/translation.json';
import ja from './locales/ja/translation.json';
import ar from './locales/ar/translation.json';

export const SUPPORTED_LANGUAGES = (
  import.meta.env.VITE_SUPPORTED_LANGUAGES || 'en,es,fr,de,pt,zh,ja,ar'
).split(',');

export const DEFAULT_LANGUAGE = import.meta.env.VITE_DEFAULT_LANGUAGE || 'en';

// ur (Urdu) is listed as a supported RTL language in the requirements
// even though it's not one of the 8 languages with translation files
// here - kept in this list so dir='rtl' still applies correctly if/when
// Urdu translations are added later, rather than silently only handling
// the 2 RTL languages that happen to already have translation files.
const RTL_LANGUAGES = ['ar', 'he', 'ur'];

const resources = {
  en: { translation: en },
  es: { translation: es },
  fr: { translation: fr },
  de: { translation: de },
  pt: { translation: pt },
  zh: { translation: zh },
  ja: { translation: ja },
  ar: { translation: ar },
};

function applyDirection(language) {
  const lang = (language || DEFAULT_LANGUAGE).split('-')[0];
  document.documentElement.dir = RTL_LANGUAGES.includes(lang) ? 'rtl' : 'ltr';
  document.documentElement.lang = lang;
}

i18n
  .use(LanguageDetector)
  .use(initReactI18next)
  .init({
    resources,
    fallbackLng: 'en',
    supportedLngs: SUPPORTED_LANGUAGES,
    ns: ['translation'],
    defaultNS: 'translation',
    interpolation: {
      escapeValue: false, // React already escapes output
    },
    detection: {
      // localStorage first (an explicit prior choice), then browser
      // settings. There is no backend "user preferences API" for
      // language yet - grepped this whole codebase for one and found
      // nothing (no column, no endpoint). syncLanguageFromPreferences()
      // below is the hook point for wiring that in once it exists,
      // rather than silently building a new backend feature as part of
      // what was asked as an SDK-setup step.
      order: ['localStorage', 'navigator'],
      caches: ['localStorage'],
      lookupLocalStorage: 'i18nextLng',
    },
  });

i18n.on('languageChanged', applyDirection);
applyDirection(i18n.language);

/**
 * Call this once you have a real backend endpoint for the user's saved
 * language preference (e.g. after fetching the user's profile). Not
 * wired to anything automatically yet, since that endpoint doesn't
 * exist in this codebase - see the comment above.
 */
export async function syncLanguageFromPreferences(preferredLanguage) {
  if (preferredLanguage && SUPPORTED_LANGUAGES.includes(preferredLanguage)) {
    await i18n.changeLanguage(preferredLanguage);
  }
}

export default i18n;
