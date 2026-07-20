export type LocaleFormattingPreferences = {
  locale?: string
  dateFormat?: Intl.DateTimeFormatOptions
  timeFormat?: Intl.DateTimeFormatOptions
  numberFormat?: Intl.NumberFormatOptions
}

function resolveLocale(preferredLocale?: string) {
  // Intl falls back naturally if undefined/invalid; keep it simple.
  return preferredLocale
}

export function formatDate(
  value: Date | string | number | null | undefined,
  prefs: LocaleFormattingPreferences,
): string {
  if (value === null || value === undefined) return ''
  const locale = resolveLocale(prefs.locale)

  const dtf = new Intl.DateTimeFormat(locale, prefs.dateFormat)
  return dtf.format(typeof value === 'string' || typeof value === 'number' ? new Date(value) : value)
}

export function formatTime(
  value: Date | string | number | null | undefined,
  prefs: LocaleFormattingPreferences,
): string {
  if (value === null || value === undefined) return ''
  const locale = resolveLocale(prefs.locale)

  const dtf = new Intl.DateTimeFormat(locale, prefs.timeFormat)
  return dtf.format(typeof value === 'string' || typeof value === 'number' ? new Date(value) : value)
}

export function formatNumber(
  value: number | string | null | undefined,
  prefs: LocaleFormattingPreferences,
): string {
  if (value === null || value === undefined) return ''
  const locale = resolveLocale(prefs.locale)

  const num = typeof value === 'string' ? Number(value) : value
  if (Number.isNaN(num)) return ''

  const nf = new Intl.NumberFormat(locale, prefs.numberFormat)
  return nf.format(num)
}

