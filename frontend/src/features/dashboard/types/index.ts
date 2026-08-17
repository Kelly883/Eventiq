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
  defaultEventFilter: string;
  defaultDateRange: string;
  expandedEventId: string | null;
  showActivityFeed: boolean;
  autoRefreshEnabled: boolean;
}
