export type SocialLink = {
  twitter?: string;
  instagram?: string;
  linkedin?: string;
  youtube?: string;
};

export type BrandingColors = {
  primaryColor: string;
  accentColor: string;
};

export type NotificationPreferences = {
  ticketSales: boolean;
  eventReminders: boolean;
  platformUpdates: boolean;
};

export type OrganizerStats = {
  totalEventsCreated: number;
  totalTicketsSold: number;
  memberSince: Date;
};

export interface Organizer {
  id: string;
  userId: string;
  displayName: string;
  bio?: string;
  avatarUrl?: string;
  email: string;
  phone?: string;
  website?: string;
  socialLinks?: SocialLink;
  brandingColors?: BrandingColors;
  isPublic: boolean;
  emailPublic: boolean;
  phonePublic: boolean;
  notificationPreferences?: NotificationPreferences;
  totalEventsCreated: number;
  totalTicketsSold: number;
  createdAt: Date;
  updatedAt: Date;
}

export interface OrganizerPublic {
  id: string;
  userId: string;
  displayName: string;
  bio?: string;
  avatarUrl?: string;
  website?: string;
  socialLinks?: SocialLink;
  brandingColors?: BrandingColors;
  totalEventsCreated: number;
  totalTicketsSold: number;
  createdAt: Date;
  email?: string;
  phone?: string;
}

export { z } from 'zod';

export const hexColorRegex = /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/;

export const urlString = z.string().url();

export const OrganizerUpdateSchema = z.object({
  displayName: z.string().min(1).max(50),
  bio: z.string().max(500).optional(),
  website: urlString.optional(),
  avatarUrl: urlString.optional(),
  socialLinks: z.object({
    twitter: urlString.optional(),
    instagram: urlString.optional(),
    linkedin: urlString.optional(),
    youtube: urlString.optional(),
  }).optional(),
  brandingColors: z.object({
    primaryColor: z.string().regex(hexColorRegex, 'Invalid hex color'),
    accentColor: z.string().regex(hexColorRegex, 'Invalid hex color'),
  }).optional(),
  isPublic: z.boolean().optional(),
  emailPublic: z.boolean().optional(),
  phonePublic: z.boolean().optional(),
  notificationPreferences: z.object({
    ticketSales: z.boolean().optional(),
    eventReminders: z.boolean().optional(),
    platformUpdates: z.boolean().optional(),
  }).optional(),
});

export const AvatarUploadSchema = z.object({
  file: z.instanceof(File)
    .refine((file) => file.type.startsWith('image/'), 'File must be an image')
    .refine((file) => file.size <= 5 * 1024 * 1024, 'File size must be less than 5MB'),
});
