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

