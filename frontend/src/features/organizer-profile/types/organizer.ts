export interface OrganizerSocialLinks {
  twitter?: string;
  instagram?: string;
  linkedin?: string;
  youtube?: string;
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
  isPublic: boolean;
  emailPublic: boolean;
  phonePublic: boolean;
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
  socialLinks: OrganizerSocialLinks | null;
  brandingColors: BrandingColors | null;
  totalEventsCreated: number;
  totalTicketsSold: number;
  createdAt: string;
  email?: string | null;
  phone?: string | null;
}
