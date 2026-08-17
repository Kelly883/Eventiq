export function formatCurrency(amount: number, currency: string, locale = 'en-US'): string {
  const symbols: Record<string, string> = {
    USD: '$',
    EUR: '€',
    GBP: '£',
    NGN: '₦',
    GHS: '₵',
    KES: 'KSh',
    ZAR: 'R',
  };

  const symbol = symbols[currency.toUpperCase()] ?? currency.toUpperCase();
  return `${symbol}${amount.toFixed(2)}`;
}
