import { format, formatDistanceToNow, isAfter, isValid, parseISO } from 'date-fns';

function toDate(input: string | Date | null | undefined): Date | null {
  if (!input) return null;
  if (input instanceof Date) return isValid(input) ? input : null;
  const d = parseISO(input);
  return isValid(d) ? d : null;
}

export function parseEventDate(input: string | Date | null | undefined): Date | null {
  return toDate(input);
}

export function formatEventDate(input: string | Date | null | undefined, pattern = 'MMM d, yyyy'): string {
  const d = toDate(input);
  if (!d) return '—';
  return format(d, pattern);
}

export function formatEndsIn(input: string | Date | null | undefined, now: Date = new Date()): string {
  const d = toDate(input);
  if (!d) return '—';
  if (isAfter(now, d)) return 'Ended';
  return `Ends in ${formatDistanceToNow(d, { addSuffix: false })}`;
}

export function isEarlyBirdActive(earlyBirdEndDate: string | Date | null | undefined, now: Date = new Date()): boolean {
  const end = toDate(earlyBirdEndDate);
  if (!end) return false;
  return now.getTime() <= end.getTime();
}

export function validateSalesWindowDates(salesStartDate: string | Date | null | undefined, salesEndDate: string | Date | null | undefined): {
  ok: boolean;
  message?: string;
} {
  const start = toDate(salesStartDate);
  const end = toDate(salesEndDate);
  if (!start || !end) {
    return { ok: false, message: 'Sales window dates are invalid.' };
  }
  if (!isAfter(end, start)) {
    return { ok: false, message: 'sales_end_date must be after sales_start_date.' };
  }
  return { ok: true };
}

export function normalizeSalesDate(input: string | Date | null | undefined): Date | null {
  const d = toDate(input);
  if (!d) return null;
  return d;
}

export function formatSalesWindow(salesStartDate: string | Date | null | undefined, salesEndDate: string | Date | null | undefined, pattern = 'MMM d, yyyy HH:mm'): string {
  const start = normalizeSalesDate(salesStartDate);
  const end = normalizeSalesDate(salesEndDate);
  if (start && end) {
    return `${format(start, pattern)} — ${format(end, pattern)}`;
  }
  if (!start && !end) return 'No sales window';
  if (start && !end) return `Starts ${format(start, pattern)}`;
  if (!start && end) return `Until ${format(end, pattern)}`;
  return '';
}

export function isSalesWindowActive(salesStartDate: string | Date | null | undefined, salesEndDate: string | Date | null | undefined, now: Date = new Date()): boolean {
  const start = normalizeSalesDate(salesStartDate);
  const end = normalizeSalesDate(salesEndDate);
  if (start && now < start) return false;
  if (end && now > end) return false;
  return true;
}

export function isEarlyBirdActiveForTier(tier: { early_bird_price?: number | null; early_bird_end_date?: string | Date | null }, now: Date = new Date()): boolean {
  if (!tier.early_bird_price && tier.early_bird_price !== 0) return false;
  if (!tier.early_bird_end_date) return false;
  const end = toDate(tier.early_bird_end_date);
  if (!end) return false;
  return now.getTime() <= end.getTime();
}

export function getEffectivePriceForTier(tier: { price: number; early_bird_price?: number | null; early_bird_end_date?: string | Date | null }, now: Date = new Date()): number {
  return isEarlyBirdActiveForTier(tier, now) ? (tier.early_bird_price as number) : tier.price;
}

export function isAvailableForTier(tier: { sales_start_date?: string | Date | null; sales_end_date?: string | Date | null; quantity?: number | null; sold_count?: number | null }, now: Date = new Date()): boolean {
  if (tier.sales_start_date && now < new Date(tier.sales_start_date)) return false;
  if (tier.sales_end_date && now > new Date(tier.sales_end_date)) return false;
  if (tier.quantity != null && (tier.sold_count ?? 0) >= tier.quantity) return false;
  return true;
}

export function getRemainingQuantity(tier: { quantity?: number | null; sold_count?: number | null }): number {
  if (tier.quantity == null) return 0;
  return Math.max(0, tier.quantity - (tier.sold_count ?? 0));
}

