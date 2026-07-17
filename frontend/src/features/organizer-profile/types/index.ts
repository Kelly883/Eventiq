// Organizer Profile feature types

export interface OrganizerProfile {
  id: number;
  user_id: number;
  business_name: string;
  bio: string | null;
  branding_color: string | null;
  logo_path: string | null;
  website_url: string | null;
  social_links: SocialLinks | null;
  privacy_settings: PrivacySettings | null;
  created_at: string;
  updated_at: string;
}

export interface SocialLinks {
  facebook?: string;
  twitter?: string;
  instagram?: string;
  linkedin?: string;
  youtube?: string;
}

export interface PrivacySettings {
  show_email: boolean;
  show_phone: boolean;
  show_social_links: boolean;
  show_past_events: boolean;
  show_upcoming_events: boolean;
}

export interface OrganizerEvent {
  id: number;
  title: string;
  description: string | null;
  start_date: string;
  end_date: string;
  location: string | null;
  cover_image_url: string | null;
  status: 'draft' | 'published' | 'cancelled' | 'completed';
}

export interface AuditLogEntry {
  id: number;
  action: string;
  field: string | null;
  old_value: string | null;
  new_value: string | null;
  created_at: string;
}

export interface OrganizerProfileUpdatePayload {
  business_name?: string;
  bio?: string | null;
  branding_color?: string | null;
  logo_path?: string | null;
  website_url?: string | null;
  social_links?: SocialLinks;
  privacy_settings?: PrivacySettings;
}

export interface ApiResponse<T> {
  data: T;
  message?: string;
}