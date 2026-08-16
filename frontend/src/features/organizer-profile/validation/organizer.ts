import { z } from 'zod';

const hexColorRegex = /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/;

export const SocialLinksSchema = z.object({
  twitter: z.string().url().optional().or(z.literal('')),
  instagram: z.string().url().optional().or(z.literal('')),
  linkedin: z.string().url().optional().or(z.literal('')),
  youtube: z.string().url().optional().or(z.literal('')),
});

export const BrandingColorsSchema = z.object({
  primaryColor: z.string().regex(hexColorRegex, 'Invalid hex color'),
  accentColor: z.string().regex(hexColorRegex, 'Invalid hex color'),
});

export const NotificationPreferencesSchema = z.object({
  ticketSales: z.boolean(),
  eventReminders: z.boolean(),
  platformUpdates: z.boolean(),
});

export const OrganizerUpdateSchema = z.object({
  displayName: z.string().min(1, 'Display name is required'),
  bio: z.string().max(500, 'Bio must be at most 500 characters').optional().nullable(),
  website: z.string().url('Invalid website URL').optional().nullable(),
  socialLinks: SocialLinksSchema.optional().nullable(),
  brandingColors: BrandingColorsSchema.optional().nullable(),
  emailPublic: z.boolean().optional(),
  phonePublic: z.boolean().optional(),
  isPublic: z.boolean().optional(),
  notificationPreferences: NotificationPreferencesSchema.optional().nullable(),
});

export const AvatarUploadSchema = z
  .instanceof(File, { message: 'File is required' })
  .refine(
    (file) => file.type.startsWith('image/'),
    'File must be an image'
  )
  .refine(
    (file) => file.size <= 5 * 1024 * 1024,
    'File size must be at most 5MB'
  );

export type OrganizerUpdateInput = z.infer<typeof OrganizerUpdateSchema>;
export type AvatarUploadInput = z.infer<typeof AvatarUploadSchema>;
