/**
 * Locale-aware formatting for dates, times, and numbers, driven by the
 * user's dateFormat/timeFormat/numberFormat/locale preferences (however
 * those are sourced - no backend endpoint for them exists yet in this
 * codebase, so callers currently need to supply these explicitly).
 */

export interface FormatPreferences {
  locale?: string; // BCP 47 tag, e.g. 'en-US', 'fr-FR'
  dateFormat?: 'short' | 'medium' | 'long' | 'full';
  timeFormat?: '12h' | '24h';
  numberFormat?: 'standard' | 'compact';
}

const DEFAULT_LOCALE = 'en-US';

function resolveLocale(preferences?: FormatPreferences): string {
  return preferences?.locale || DEFAULT_LOCALE;
}

export function formatDate(date: Date | string | number, preferences?: FormatPreferences): string {
  const d = date instanceof Date ? date : new Date(date);
  const locale = resolveLocale(preferences);
  const dateStyle = preferences?.dateFormat || 'medium';

  return new Intl.DateTimeFormat(locale, { dateStyle }).format(d);
}

export function formatTime(date: Date | string | number, preferences?: FormatPreferences): string {
  const d = date instanceof Date ? date : new Date(date);
  const locale = resolveLocale(preferences);
  const hour12 = (preferences?.timeFormat ?? '12h') === '12h';

  return new Intl.DateTimeFormat(locale, {
    hour: 'numeric',
    minute: '2-digit',
    hour12,
  }).format(d);
}

export function formatDateTime(date: Date | string | number, preferences?: FormatPreferences): string {
  const d = date instanceof Date ? date : new Date(date);
  const locale = resolveLocale(preferences);
  const dateStyle = preferences?.dateFormat || 'medium';
  const hour12 = (preferences?.timeFormat ?? '12h') === '12h';

  return new Intl.DateTimeFormat(locale, {
    dateStyle,
    timeStyle: 'short',
    hour12,
  }).format(d);
}

export function formatNumber(value: number, preferences?: FormatPreferences): string {
  const locale = resolveLocale(preferences);
  const notation = preferences?.numberFormat === 'compact' ? 'compact' : 'standard';

  return new Intl.NumberFormat(locale, { notation }).format(value);
}

export function formatCurrency(value: number, currencyCode: string, preferences?: FormatPreferences): string {
  const locale = resolveLocale(preferences);

  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency: currencyCode,
  }).format(value);
}
