import { z } from 'zod';

export const hexColorSchema = z
  .string()
  .regex(/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/, 'Invalid hex color format')
  .optional()
  .nullable();

export const urlSchema = z
  .string()
  .url('Invalid URL format')
  .optional()
  .nullable();

export const emailSchema = z
  .string()
  .email('Invalid email format')
  .optional()
  .nullable();

export const phoneSchema = z
  .string()
  .regex(/^\+?[1-9]\d{1,14}$/, 'Invalid phone number format')
  .optional()
  .nullable();

export const socialLinksSchema = z.object({
  facebook: z.string().url().optional().nullable(),
  twitter: z.string().url().optional().nullable(),
  instagram: z.string().url().optional().nullable(),
  linkedin: z.string().url().optional().nullable(),
  youtube: z.string().url().optional().nullable(),
});

export const privacySettingsSchema = z.object({
  show_email: z.boolean(),
  show_phone: z.boolean(),
  show_social_links: z.boolean(),
  show_past_events: z.boolean(),
  show_upcoming_events: z.boolean(),
});

export const organizerProfileSchema = z.object({
  business_name: z.string().min(1, 'Business name is required'),
  display_name: z.string().min(1, 'Display name is required').optional().nullable(),
  bio: z.string().max(500, 'Bio must be 500 characters or less').optional().nullable(),
  branding_color: hexColorSchema,
  logo_path: z.string().optional().nullable(),
  avatar_url: z.string().url().optional().nullable(),
  email: emailSchema,
  phone: phoneSchema,
  website_url: urlSchema,
  social_links: socialLinksSchema.optional().nullable(),
  privacy_settings: privacySettingsSchema.optional().nullable(),
  is_public: z.boolean().optional(),
});

export const organizerPublicSchema = z.object({
  id: z.number(),
  user_id: z.number(),
  business_name: z.string(),
  display_name: z.string().optional().nullable(),
  bio: z.string().optional().nullable(),
  branding_color: hexColorSchema,
  logo_path: z.string().optional().nullable(),
  avatar_url: z.string().optional().nullable(),
  email: z.string().optional().nullable(),
  phone: z.string().optional().nullable(),
  website_url: urlSchema,
  social_links: socialLinksSchema.optional().nullable(),
  is_public: z.boolean(),
  created_at: z.string(),
  updated_at: z.string(),
});

export const avatarUploadSchema = z.object({
  file: z
    .instanceof(File)
    .refine((file) => file.size <= 2 * 1024 * 1024, 'Avatar must be less than 2MB')
    .refine(
      (file) => ['image/jpeg', 'image/png', 'image/webp'].includes(file.type),
      'Avatar must be a JPEG, PNG, or WebP image'
    ),
});

export type OrganizerProfileInput = z.infer<typeof organizerProfileSchema>;
export type OrganizerPublicInput = z.infer<typeof organizerPublicSchema>;
export type AvatarUploadInput = z.infer<typeof avatarUploadSchema>;
