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

export const socialLinksSchema = z.object({
  facebook: z.string().url().optional().nullable(),
  twitter: z.string().url().optional().nullable(),
  instagram: z.string().url().optional().nullable(),
  linkedin: z.string().url().optional().nullable(),
  youtube: z.string().url().optional().nullable(),
});

export const organizerProfileSchema = z.object({
  displayName: z.string().min(1, 'Display name is required'),
  bio: z.string().max(500, 'Bio must be 500 characters or less').optional().nullable(),
  branding_color: hexColorSchema,
  website_url: urlSchema,
  social_links: socialLinksSchema.optional().nullable(),
  logo_path: z.string().optional().nullable(),
});

export const organizerPublicSchema = z.object({
  id: z.number(),
  user_id: z.number(),
  business_name: z.string(),
  bio: z.string().optional().nullable(),
  branding_color: hexColorSchema,
  logo_path: z.string().optional().nullable(),
  website_url: urlSchema,
  social_links: socialLinksSchema.optional().nullable(),
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
