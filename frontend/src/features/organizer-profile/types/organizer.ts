export interface OrganizerSocialLinks {
  twitter?: string | null;
  instagram?: string | null;
  linkedin?: string | null;
  youtube?: string | null;
}

export interface BrandingColors {
  primaryColor: string;
  accentColor: string;
}

export interface NotificationPreferences {
  ticketSales: boolean;
  eventReminders: boolean;
  platformUpdates: boolean;
}

export interface OrganizerStats {
  totalEventsCreated: number;
  totalTicketsSold: number;
  memberSince: Date;
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
  socialLinks: OrganizerSocialLinks | null;
  brandingColors: BrandingColors | null;
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
  notificationPreferences: NotificationPreferences | null;
  totalEventsCreated: number;
  totalTicketsSold: number;
  createdAt: string;
  updatedAt: string;
  deletedAt: string | null;
}

export interface OrganizerPublic {
  id: number;
  userId: string;
  displayName: string;
  bio: string | null;
  avatarUrl: string | null;
  website: string | null;
  socialLinks?: OrganizerSocialLinks | null;
  brandingColors?: BrandingColors | null;
  totalEventsCreated: number;
  totalTicketsSold: number;
  createdAt: string;
  email?: string | null;
  phone?: string | null;
}
