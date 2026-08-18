import type { Event as EventModel } from '@/features/events/types/shared';

export interface DashboardOverview {
  totalTickets: number;
  upcomingEventsCount: number;
  pastEventsCount: number;
  totalSpent: number;
  totalRevenue: number;
}

export interface PaginationMeta {
  readonly currentPage: number;
  readonly lastPage: number;
  readonly perPage: number;
  readonly total: number;
  readonly from: number | null;
  readonly to: number | null;
}

export interface EventMetrics {
  id: number;
  title: string;
  startDate: string;
  endDate: string;
  status: 'draft' | 'published' | 'archived';
  thumbnailUrl: string | null;
  totalRevenue: number;
  totalTicketsSold: number;
  ticketsAvailable: number;
  utilizationPercentage: number;
  conversionRate: number;
}

export interface TierObject {
  id: string;
  name: string;
  price: number;
  quantity: number;
  soldCount: number;
}

export interface PricingWindowObject {
  id: string;
  windowName: string;
  startDateTime: string;
  endDateTime: string;
  price: number;
  isActive: boolean;
}

export interface EventDetail {
  eventId: number;
  title: string;
  startDate: string;
  endDate: string;
  status: 'draft' | 'published' | 'archived';
  totalRevenue: number;
  totalTicketsSold: number;
  conversionRate: number;
  tiers: TierObject[];
  pricingWindows: PricingWindowObject[];
}

export interface ActivityItem {
  id: number;
  eventId: number | string;
  eventName: string;
  tierId: number | string;
  tierName: string;
  quantity: number;
  unitPrice: number;
  totalAmount: number;
  saleTimestamp: string;
  buyerEmail: string | null;
}

export interface DashboardPreferences {
  defaultEventFilter: string;
  defaultDateRange: string;
  expandedEventId: number | null;
  showActivityFeed: boolean;
  autoRefreshEnabled: boolean;
}

export type TicketFilter = 'all' | 'upcoming' | 'past';

export type DateRange = '7days' | '30days' | '90days' | 'all';

export interface Event {
  readonly id: number;
  readonly title: string;
  readonly date: string;
  readonly venue: string;
  readonly organizer: string;
}

export interface Ticket {
  readonly id: string;
  readonly eventId: string;
  readonly eventTitle: string;
  readonly eventDate: string;
  readonly eventVenue: string;
  readonly tierId: string;
  readonly tierName: string;
  readonly qrCodeData: string;
  readonly status: string;
  readonly deliveryStatus: string;
  readonly deliveryMethod: string;
  readonly deliveryTimestamp: string | null;
  readonly orderId: string;
}

export interface DashboardSummary {
  readonly totalTickets: number;
  readonly upcomingEventsCount: number;
  readonly pastEventsCount: number;
  readonly totalSpent: number;
  readonly totalRevenue: number;
}

export interface ActivityFeedItem {
  readonly eventTitle: string;
  readonly tierName: string;
  readonly purchaseDate: string;
  readonly deliveryStatus: string;
}

export interface DeliveryEvent {
  readonly timestamp: string;
  readonly status: string;
  readonly method: string;
  readonly errorMessage: string | null;
}
