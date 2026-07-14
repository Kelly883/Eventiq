/**
 * Shared constants for the event calendar feature.
 */

/** Canonical wire format for dates sent to/from the Laravel API. */
export const DATE_FORMAT = "yyyy-MM-dd'T'HH:mm:ssxxx"; // ISO 8601, e.g. 2026-07-14T09:00:00+01:00

/** Human-readable format for display in the UI. */
export const DISPLAY_FORMAT = 'EEE, MMM d, yyyy · h:mm a'; // e.g. "Tue, Jul 14, 2026 · 9:00 AM"

export const CALENDAR_VIEWS = {
  MONTH: 'month',
  WEEK: 'week',
  DAY: 'day',
} as const;

export type CalendarView = (typeof CALENDAR_VIEWS)[keyof typeof CALENDAR_VIEWS];

/** Ticket availability status → color, used for calendar day/event badges. */
export const AVAILABILITY_COLORS = {
  available: '#16a34a', // green-600
  lowStock: '#d97706', // amber-600
  soldOut: '#dc2626', // red-600
} as const;

export type AvailabilityStatus = keyof typeof AVAILABILITY_COLORS;
