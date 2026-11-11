/**
 * Locale-aware currency formatting using the browser Intl API, with a
 * symbol fallback for currencies Intl may not render natively.
 *
 * @param amount - numeric amount in major units (e.g. 10.50)
 * @param currency - ISO 4217 currency code (e.g. "USD", "NGN")
 * @param locale - BCP 47 locale tag (defaults to "en-US")
 */
export function formatCurrency(
  amount: number,
  currency: string,
  locale: string = 'en-US',
): string {
  if (!Number.isFinite(amount)) {
    return `${currency.toUpperCase()} 0.00`;
  }

  try {
    return new Intl.NumberFormat(locale, {
      style: 'currency',
      currency: currency.toUpperCase(),
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(amount);
  } catch {
    // Fallback: Intl may not support every locale/currency combo.
    const symbol =
      {
        USD: '$',
        EUR: '€',
        GBP: '£',
        NGN: '₦',
        GHS: '₵',
        KES: 'KSh',
        ZAR: 'R',
      }[currency.toUpperCase()] ?? currency.toUpperCase();
    return `${symbol}${amount.toFixed(2)}`;
  }
}

/**
 * Formats a fee/tax line item with an optional label for display in a
 * breakdown table.
 */
export function formatFeeLine(
  label: string,
  amount: number,
  currency: string,
  locale = 'en-US',
): { label: string; amount: string } {
  return {
    label,
    amount: formatCurrency(amount, currency, locale),
  };
}
