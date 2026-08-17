export interface Event {
  id: number;
  user_id: number | null;
  organizer_id: number;
  title: string;
  description: string;
  banner_image_url: string | null;
  start_datetime: string;
  end_datetime: string;
  venue_name: string;
  venue_address: string | null;
  latitude: number | null;
  longitude: number | null;
  capacity: number;
  status: 'draft' | 'published' | 'archived';
  category: string | null;
  deleted_at: string | null;
  ticket_tiers: TicketTier[];
  created_at: string;
  updated_at: string;
}

export interface Organizer {
  id: number;
  user_id: number;
  userId: string;
  displayName: string;
  bio: string | null;
  avatarUrl: string | null;
  email: string | null;
  phone: string | null;
  website: string | null;
  socialLinks: Record<string, string | null> | null;
  brandingColors: Record<string, string> | null;
  timezone: string | null;
  currency: string | null;
  country: string | null;
  verificationStatus: string | null;
  paymentDefault: string | null;
  commissionRate: number | null;
  isPublic: boolean;
  emailPublic: boolean;
  phonePublic: boolean;
  hideSocialLinks: boolean;
  hideBrandingColors: boolean;
  notificationPreferences: Record<string, any> | null;
  totalEventsCreated: number;
  totalTicketsSold: number;
  createdAt: string;
  updatedAt: string;
  deletedAt: string | null;
}

export interface TicketTier {
  id: number;
  event_id: number;
  name: string;
  description: string | null;
  price: number;
  min_purchase: number;
  max_purchase: number | null;
  early_bird_price: number | null;
  early_bird_end_date: string | null;
  is_active: boolean;
  quantity: number;
  sales_start_date: string | null;
  sales_end_date: string | null;
  benefits_description: string | null;
  tier_image_url: string | null;
  max_per_customer: number | null;
  tier_order: number;
  status: 'draft' | 'published' | 'archived';
  currency: string;
  voucher_code: string | null;
  sales_channel: string | null;
  published_at: string | null;
  created_by: number | null;
  updated_by: number | null;
  sold_count: number;
  is_visible: boolean;
  is_sold_out: boolean;
  allow_repurchase: boolean;
  available_count: number | null;
  created_at: string;
  updated_at: string;
}

export interface PricingWindow {
  id: string;
  event_id: string;
  ticket_category_id: string | null;
  window_name: string;
  start_date_time: string;
  end_date_time: string;
  price: number;
  quantity_limit: number | null;
  quantity_sold: number;
  is_active: boolean;
  priority: number;
  created_at: string;
  updated_at: string;
}

export interface AnalyticsEventsMetrics {
  id: string;
  event_id: string;
  organizer_id: string | null;
  total_revenue: string;
  total_tickets_sold: number;
  total_page_views: number;
  total_ticket_page_views: number;
  conversion_rate: string;
  average_ticket_price: string;
  peak_sales_hour: number | null;
  top_ticket_tier_id: string | null;
  last_updated_at: string;
}
