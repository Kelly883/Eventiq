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

/* ---------------------------------------------------------------------------
 * Public discovery payload shapes (App\Features\Public\Resources\EventResource
 * and App\Http\Resources\EventPublicResource).
 *
 * The two public event endpoints deliberately expose DIFFERENT field names
 * (snake_case EventResource vs. camelCase EventPublicResource aliases like
 * start_date/venue/image_url) — the discovery UI is written against the
 * union so a card renders correctly no matter which endpoint the page calls.
 * ------------------------------------------------------------------------- */

/** Organizer as embedded by Features\Public EventResource: { name, avatar }. */
export interface PublicOrganizerSummary {
  readonly name?: string | null;
  readonly avatar?: string | null;
}

/** Ticket tier as embedded by Features\Public EventResource. */
export interface PublicTicketTier {
  readonly id: number;
  readonly name: string;
  readonly price: number;
  readonly currency?: string | null;
  readonly quantity?: number | null;
  readonly sold_count?: number | null;
  readonly is_active?: boolean;
  readonly status?: string | null;
  /** Present on Features\Public ticket-tiers endpoint. */
  readonly available_count?: number | null;
  /** Present on EventPublicResource ticket_tiers. */
  readonly is_available?: boolean;
}

/** Full public event shape (Features\Public EventResource). */
export interface PublicEvent {
  readonly id: number;
  readonly title: string;
  readonly slug?: string | null;
  readonly description?: string | null;
  readonly start_datetime?: string | null;
  readonly end_datetime?: string | null;
  readonly status?: string | null;
  readonly category?: string | null;
  readonly organizer?: PublicOrganizerSummary | null;
  readonly ticket_tiers?: PublicTicketTier[];
  readonly ticket_availability?: number | null;
  readonly popularity_score?: number | null;
  /* EventPublicResource aliases */
  readonly name?: string | null;
  readonly start_date?: string | null;
  readonly end_date?: string | null;
  readonly venue?: string | null;
  readonly venue_name?: string | null;
  readonly location?: string | null;
  readonly image_url?: string | null;
  readonly banner_image_url?: string | null;
  readonly ticket_price?: number | null;
  readonly tickets_remaining?: number | null;
}

/** Category option returned by GET /events/public/filters. */
export interface DiscoveryCategory {
  readonly id?: string | number;
  readonly slug?: string;
  readonly name: string;
  readonly events_count?: number;
}

/** Price range returned by GET /events/public/filters. */
export interface PriceRange {
  readonly min: number;
  readonly max: number;
}

/** Filters payload returned by GET /events/public/filters. */
export interface DiscoveryFiltersPayload {
  readonly categories: Array<string | DiscoveryCategory>;
  readonly price_range?: PriceRange;
}

/** One autocomplete hit from GET /events/public/search. */
export interface SearchSuggestion {
  readonly id: number;
  readonly title: string;
  readonly slug?: string | null;
  readonly category?: string | null;
  readonly start_datetime?: string | null;
  readonly banner_image_url?: string | null;
}

/** Pricing info from GET /events/public/{event}/pricing (or /events/{id}/pricing). */
export interface PricingWindowSummary {
  readonly window_id?: string | number;
  readonly window_name?: string | null;
  readonly price: number;
  readonly start_date_time?: string | null;
  readonly end_date_time?: string | null;
  readonly available_quantity?: number | null;
}

export interface EventPricingInfo {
  readonly event_id?: string | number;
  readonly current_pricing?: PricingWindowSummary | null;
  readonly next_pricing?: PricingWindowSummary | null;
  readonly ticket_tiers?: PublicTicketTier[];
}

/** Props shared by the reusable discovery presentational components. */
export interface PricingWindowProps {
  readonly currentPrice?: number | null;
  readonly nextPrice?: number | null;
  readonly nextPriceChangeAt?: string | null;
  readonly currency?: string;
}

export interface OrganizerCardData {
  readonly id?: number | string;
  readonly name?: string | null;
  readonly displayName?: string | null;
  readonly bio?: string | null;
  readonly avatar?: string | null;
  readonly avatarUrl?: string | null;
  readonly publicUrl?: string | null;
  readonly website?: string | null;
}

/** Reusable component data types (loose, for discovery UI). */
export interface SharedOrganizer {
  readonly id?: number | string;
  readonly name?: string | null;
  readonly displayName?: string | null;
  readonly avatar?: string | null;
  readonly avatarUrl?: string | null;
  readonly bio?: string | null;
  readonly publicUrl?: string | null;
  readonly website?: string | null;
}

export interface SharedTicketTier {
  readonly id?: number | string;
  readonly name?: string | null;
  readonly price?: number | string | null;
  readonly currency?: string | null;
  readonly quantity?: number | null;
  readonly sold_count?: number | null;
  readonly available_count?: number | null;
  readonly is_active?: boolean | null;
  readonly status?: string | null;
}

export interface PricingWindowData {
  readonly currentPrice?: number | string | null;
  readonly nextPrice?: number | string | null;
  readonly nextPriceChangeAt?: string | Date | null;
}
