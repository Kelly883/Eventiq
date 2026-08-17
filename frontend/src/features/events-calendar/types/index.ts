export type EventStatus = 'draft' | 'published' | 'archived';
export type Availability = 'available' | 'low' | 'sold_out';

export interface Venue {
  readonly name: string;
  readonly address?: string | null;
  readonly latitude: number;
  readonly longitude: number;
}

export interface Organizer {
  readonly id: string;
  readonly name: string;
  readonly avatar?: string | null;
  readonly brandingColors: {
    readonly primary: string;
    readonly secondary: string;
  };
  readonly privacySettings: Record<string, unknown>;
}

export interface TicketTier {
  readonly id: string;
  readonly name: string;
  readonly currentPrice: number;
  readonly ticketsAvailable: number;
  readonly totalTickets: number;
  readonly soldCount?: number;
  readonly isSoldOut?: boolean;
}

export interface PricingWindow {
  readonly id: string;
  readonly eventId: string;
  readonly ticketTierId: string;
  readonly startDate: string;
  readonly endDate: string;
  readonly price: number;
}

export interface TicketInventory {
  readonly id: string;
  readonly eventId: string;
  readonly ticketTierId: string;
  readonly totalTickets: number;
  readonly ticketsSold: number;
  readonly ticketsAvailable: number;
}

export interface Event {
  readonly id: string;
  readonly organizerId: string;
  readonly title: string;
  readonly description: string;
  readonly status: EventStatus;
  readonly eventDate: string;
  readonly startTime: string;
  readonly endTime: string;
  readonly category: string;
  readonly venue: Venue;
  readonly bannerUrl?: string | null;
  readonly organizer: Organizer;
  readonly ticketTiers: readonly TicketTier[];
  readonly ticketsSold: number;
  readonly trending: boolean;
  readonly deletedAt?: string | null;
}

export interface CalendarDate {
  readonly date: string;
  readonly eventCount: number;
  readonly availability: Availability;
  readonly events: readonly Event[];
}

export interface CalendarResponse {
  readonly dates: readonly CalendarDate[];
  readonly month: string;
  readonly week: string;
}

export interface DayDetailResponse {
  readonly date: string;
  readonly events: readonly Event[];
  readonly total: number;
}

export interface RangeResponse {
  readonly startDate: string;
  readonly endDate: string;
  readonly dates: readonly CalendarDate[];
  readonly totalEvents: number;
}

export function isEventAvailable(event: Event): boolean {
  return event.status === 'published';
}

export function getAvailabilityStatus(
  totalTickets: number,
  ticketsSold: number,
): Availability {
  const available = totalTickets - ticketsSold;

  if (available <= 0) {
    return 'sold_out';
  }

  if (available <= Math.max(1, totalTickets * 0.1)) {
    return 'low';
  }

  return 'available';
}

export function formatEventDate(dateString: string): string {
  const date = new Date(dateString);

  if (Number.isNaN(date.getTime())) {
    return dateString;
  }

  return date.toLocaleDateString('en-US', {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
}

export function groupEventsByDate(events: readonly Event[]): Map<string, Event[]> {
  const map = new Map<string, Event[]>();

  for (const event of events) {
    const key = event.eventDate;
    const existing = map.get(key) ?? [];

    existing.push(event);
    map.set(key, existing);
  }

  return map;
}
