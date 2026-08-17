export interface DashboardOverview {
  totalRevenue: number;
  totalTicketsSold: number;
  averageConversionRate: number;
  upcomingEventCount: number;
  totalEventCount: number;
  revenueTrend: 'up' | 'down' | 'flat';
  ticketsTrend: 'up' | 'down' | 'flat';
  conversionTrend: 'up' | 'down' | 'flat';
  lastUpdatedAt: string;
}

export interface EventMetrics {
  id: number;
  title: string;
  date: string;
  status: 'draft' | 'published' | 'archived';
  thumbnailUrl: string;
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
  date: string;
  status: 'draft' | 'published' | 'archived';
  totalRevenue: number;
  totalTicketsSold: number;
  conversionRate: number;
  tiers: TierObject[];
  pricingWindows: PricingWindowObject[];
}

export interface ActivityItem {
  id: number;
  eventId: number;
  eventName: string;
  tierId: number;
  tierName: string;
  quantity: number;
  unitPrice: number;
  totalAmount: number;
  saleTimestamp: string;
  buyerEmail: string | null;
}

export interface DashboardPreferences {
  defaultTicketFilter: string;
  defaultDateRange: string;
  showRecommendations: boolean;
  showActivityFeed: boolean;
  autoRefreshEnabled: boolean;
}

export type TicketFilter = 'all' | 'upcoming' | 'past';

export type DateRange = '7days' | '30days' | '90days' | 'all';

export interface Event {
  readonly id: string;
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
