import { z } from 'zod';

const hexColorRegex = /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/;

const normalizeSocialLinks = z.object({
  twitter: z.string().url().optional().or(z.literal('')).nullable(),
  instagram: z.string().url().optional().or(z.literal('')).nullable(),
  linkedin: z.string().url().optional().or(z.literal('')).nullable(),
  youtube: z.string().url().optional().or(z.literal('')).nullable(),
}).transform((val) => {
  if (!val || typeof val !== 'object') return val;
  const cleaned: Record<string, string | null> = {};
  for (const [key, value] of Object.entries(val)) {
    if (value === '' || value === undefined || value === null) {
      cleaned[key] = null;
    } else {
      cleaned[key] = value;
    }
  }
  return cleaned;
});

export const SocialLinksSchema = z.object({
  twitter: z.string().url().optional().or(z.literal('')).nullable(),
  instagram: z.string().url().optional().or(z.literal('')).nullable(),
  linkedin: z.string().url().optional().or(z.literal('')).nullable(),
  youtube: z.string().url().optional().or(z.literal('')).nullable(),
});

export const BrandingColorsSchema = z.object({
  primaryColor: z.string().regex(hexColorRegex, 'Invalid hex color').optional().nullable(),
  accentColor: z.string().regex(hexColorRegex, 'Invalid hex color').optional().nullable(),
}).transform((val) => {
  if (!val || typeof val !== 'object') return val;
  const cleaned: Record<string, string | null> = {};
  for (const [key, value] of Object.entries(val)) {
    if (value && typeof value === 'string') {
      cleaned[key] = value.toLowerCase().replace(/(^#[0-9a-f]{3})(?![0-9a-f])/i, (_, short) => {
        return '#' + short[1] + short[1] + short[2] + short[2] + short[3] + short[3];
      });
    } else {
      cleaned[key] = value ?? null;
    }
  }
  return cleaned;
});

export const NotificationPreferencesSchema = z.object({
  ticketSales: z.boolean(),
  eventReminders: z.boolean(),
  platformUpdates: z.boolean(),
});

export const OrganizerUpdateSchema = z.object({
  displayName: z.string().min(1, 'Display name is required'),
  bio: z.string().max(500, 'Bio must be at most 500 characters').optional().nullable(),
  avatarUrl: z.string().url().optional().nullable().or(z.literal('')),
  email: z.string().email().optional().nullable().or(z.literal('')),
  phone: z.string().optional().nullable().or(z.literal('')),
  website: z.string().url().optional().nullable().or(z.literal('')),
  socialLinks: SocialLinksSchema.optional().nullable(),
  brandingColors: BrandingColorsSchema.optional().nullable(),
  timezone: z.string().optional().nullable().or(z.literal('')),
  currency: z.string().length(3).optional().nullable().or(z.literal('')),
  country: z.string().length(2).optional().nullable().or(z.literal('')),
  verificationStatus: z.string().optional().nullable().or(z.literal('')),
  paymentDefault: z.string().optional().nullable().or(z.literal('')),
  commissionRate: z.number().min(0).max(100).optional().nullable(),
  isPublic: z.boolean().optional(),
  emailPublic: z.boolean().optional(),
  phonePublic: z.boolean().optional(),
  hideSocialLinks: z.boolean().optional(),
  hideBrandingColors: z.boolean().optional(),
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
