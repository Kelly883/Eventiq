export type EventStatus = 'draft' | 'published' | 'archived';

export interface Organizer {
  readonly id: number;
  readonly user_id: number;
  readonly userId: string;
  readonly displayName: string;
  readonly bio: string | null;
  readonly avatarUrl: string | null;
  readonly email: string | null;
  readonly phone: string | null;
  readonly website: string | null;
  readonly socialLinks: Record<string, string | null> | null;
  readonly brandingColors: {
    readonly primary: string;
    readonly secondary: string;
  } | null;
  readonly timezone: string | null;
  readonly currency: string | null;
  readonly country: string | null;
  readonly verificationStatus: string | null;
  readonly paymentDefault: string | null;
  readonly commissionRate: number | null;
  readonly isPublic: boolean;
  readonly emailPublic: boolean;
  readonly phonePublic: boolean;
  readonly hideSocialLinks: boolean;
  readonly hideBrandingColors: boolean;
  readonly notificationPreferences: {
    readonly emailNotifications: boolean;
    readonly smsNotifications: boolean;
    readonly pushNotifications: boolean;
    readonly marketingEmails: boolean;
  } | null;
  readonly totalEventsCreated: number;
  readonly totalTicketsSold: number;
  readonly createdAt: string;
  readonly updatedAt: string;
  readonly deletedAt: string | null;
}

export interface TicketTier {
  readonly id: number;
  readonly event_id: number;
  readonly name: string;
  readonly description: string | null;
  readonly price: number;
  readonly min_purchase: number;
  readonly max_purchase: number | null;
  readonly early_bird_price: number | null;
  readonly early_bird_end_date: string | null;
  readonly is_active: boolean;
  readonly quantity: number;
  readonly sales_start_date: string | null;
  readonly sales_end_date: string | null;
  readonly benefits_description: string | null;
  readonly tier_image_url: string | null;
  readonly max_per_customer: number | null;
  readonly tier_order: number;
  readonly status: 'draft' | 'published' | 'archived';
  readonly currency: string;
  readonly voucher_code: string | null;
  readonly sales_channel: string | null;
  readonly published_at: string | null;
  readonly created_by: number | null;
  readonly updated_by: number | null;
  readonly sold_count: number;
  readonly is_visible: boolean;
  readonly is_sold_out: boolean;
  readonly allow_repurchase: boolean;
  readonly available_count: number | null;
  readonly created_at: string;
  readonly updated_at: string;
}

export interface PricingWindow {
  readonly id: string;
  readonly event_id: number;
  readonly ticket_category_id: number | null;
  readonly window_name: string;
  readonly start_date_time: string;
  readonly end_date_time: string;
  readonly price: number;
  readonly quantity_limit: number | null;
  readonly quantity_sold: number;
  readonly is_active: boolean;
  readonly priority: number;
  readonly created_at: string;
  readonly updated_at: string;
}

export interface AnalyticsEventsMetrics {
  readonly id: string;
  readonly event_id: number;
  readonly organizer_id: number | null;
  readonly total_revenue: string;
  readonly total_tickets_sold: number;
  readonly total_page_views: number;
  readonly total_ticket_page_views: number;
  readonly conversion_rate: string;
  readonly average_ticket_price: string;
  readonly peak_sales_hour: number | null;
  readonly top_ticket_tier_id: number | null;
  readonly last_updated_at: string;
  readonly trend: string;
  readonly revenue_trend: string;
  readonly tickets_sold_trend: string;
  readonly conversion_rate_trend: string;
  readonly created_at: string;
  readonly updated_at: string;
}

export interface Event {
  readonly id: number;
  readonly user_id: number | null;
  readonly organizer_id: number;
  readonly title: string;
  readonly description: string;
  readonly banner_image_url: string | null;
  readonly start_datetime: string;
  readonly end_datetime: string;
  readonly venue_name: string;
  readonly venue_address: string | null;
  readonly latitude: number | null;
  readonly longitude: number | null;
  readonly capacity: number;
  readonly status: EventStatus;
  readonly category: string | null;
  readonly deleted_at: string | null;
  readonly ticket_tiers: TicketTier[];
  readonly organizer: Organizer;
  readonly analyticsMetrics: AnalyticsEventsMetrics | null;
  readonly created_at: string;
  readonly updated_at: string;
}
