import i18n from 'i18next'

import { initReactI18next } from 'react-i18next'
import LanguageDetector, { type DetectorOptions } from 'i18next-browser-languagedetector'

const DEFAULT_LANGUAGE = 'en'

export const SUPPORTED_LANGUAGES = [
  'en',
  'es',
  'fr',
  'de',
  'pt',
  'zh',
  'ja',
  'ar',
]

const FALLBACK_LANGUAGE = 'en'
const NAMESPACE = 'translation'

const RTL_LANGUAGES = new Set(['ar', 'he', 'ur'])

function isRtlLanguage(language?: string | null) {
  if (!language) return false
  return RTL_LANGUAGES.has(String(language))
}

function applyDirToDocument(language?: string | null) {
  const dir = isRtlLanguage(language) ? 'rtl' : 'ltr'
  // document root
  if (typeof document !== 'undefined' && document.documentElement) {
    document.documentElement.setAttribute('dir', dir)
  }
}

async function fetchUserPreferredLanguage() {
  // “user preferences API” — expected to exist on backend.
  // If it fails / is not present, fallback to null.
  const baseUrl = import.meta.env.VITE_API_BASE_URL || ''

  // Conservative default endpoint; backend can be adjusted later.
  const url = `${baseUrl}/api/user/preferences/language`

  try {
    const res = await fetch(url, {
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
      },
    })

    if (!res.ok) return null
    const data = await res.json()

    const lang = data?.language
    if (typeof lang !== 'string') return null
    return SUPPORTED_LANGUAGES.includes(lang) ? lang : null
  } catch {
    return null
  }
}

const detectorOptions: DetectorOptions = {
  order: ['localStorage', 'querystring', 'navigator'],
  caches: ['localStorage'],
  lookupLocalStorage: 'language',
  // i18next-browser-languagedetector doesn’t support async detection hooks directly
  // so we’ll complement it via init()’s detection step below.
}

// Initializes i18next for feature-scoped translations.
export async function initAccessibilityLocalizationI18n() {
  if (i18n.isInitialized) return i18n

  i18n
    .use(initReactI18next)
    .use(LanguageDetector)

  i18n.on('languageChanged', (lng) => {
    applyDirToDocument(lng)
  })

  // Apply initial dir immediately (best-effort based on current language).
  applyDirToDocument(
    typeof window !== 'undefined'
      ? (localStorage.getItem('language') as string | null)
      : null,
  )

  i18n.init({
    defaultNS: NAMESPACE,
    ns: [NAMESPACE],
    resources: {},
    fallbackLng: FALLBACK_LANGUAGE,
    supportedLngs: SUPPORTED_LANGUAGES,

    interpolation: {
      escapeValue: false,
    },

    detection: detectorOptions,

    // Load translation files from local feature path.
    backend: {
      // Not used; we load via dynamic resources.
    },

    // load translation via dynamic import per language
    // eslint-disable-next-line @typescript-eslint/no-misused-promises
    async load(lng: string, namespace: string, callback: (err: unknown, res: unknown) => void) {
      try {
        const lang = SUPPORTED_LANGUAGES.includes(lng) ? lng : DEFAULT_LANGUAGE
        const mod = await import(`./locales/${lang}/${namespace}.json`)
        callback(null, mod.default ?? mod)
      } catch (e) {
        callback(e, {})
      }
    },
  } as any)

  // Complement detection with user preferences API (async best-effort).
  const preferred = await fetchUserPreferredLanguage()
  if (preferred) {
    await i18n.changeLanguage(preferred)
  } else {
    // Ensure it still uses detected language or fallback.
    await i18n.changeLanguage(i18n.language || DEFAULT_LANGUAGE)
  }

  return i18n
}

