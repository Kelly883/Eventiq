/**
 * @typedef {'event_management' | 'ticket_management' | 'analytics' | 'user_management' | 'platform_admin'} PermissionCategory
 * @typedef {'low' | 'medium' | 'high'} RiskLevel
 * @typedef {'pending' | 'approved' | 'denied'} PermissionRequestStatus
 */

/**
 * @typedef {Object} SocialLinks
 * @property {string} [twitter]
 * @property {string} [instagram]
 * @property {string} [linkedin]
 * @property {string} [youtube]
 */

/**
 * @typedef {Object} BrandingColors
 * @property {string} primaryColor
 * @property {string} accentColor
 */

/**
 * @typedef {Object} NotificationPreferences
 * @property {boolean} ticketSales
 * @property {boolean} eventReminders
 * @property {boolean} platformUpdates
 */

/**
 * @typedef {Object} OrganizerStats
 * @property {number} totalEventsCreated
 * @property {number} totalTicketsSold
 * @property {Date} memberSince
 */

/**
 * @typedef {Object} Organizer
 * @property {string} id
 * @property {string} userId
 * @property {string} displayName
 * @property {string} [bio]
 * @property {string} [logoUrl]
 * @property {string} [avatarUrl]
 * @property {string} email
 * @property {string} [phone]
 * @property {string} [website]
 * @property {SocialLinks} [socialLinks]
 * @property {BrandingColors} [brandingColors]
 * @property {string} [brandingColor]
 * @property {boolean} isPublic
 * @property {boolean} emailPublic
 * @property {boolean} phonePublic
 * @property {NotificationPreferences} [notificationPreferences]
 * @property {number} totalEventsCreated
 * @property {number} totalTicketsSold
 * @property {Date} createdAt
 * @property {Date} updatedAt
 */

/**
 * @typedef {Object} OrganizerPublic
 * @property {string} id
 * @property {string} userId
 * @property {string} displayName
 * @property {string} [bio]
 * @property {string} [logoUrl]
 * @property {string} [avatarUrl]
 * @property {string} [website]
 * @property {SocialLinks} [socialLinks]
 * @property {BrandingColors} [brandingColors]
 * @property {number} totalEventsCreated
 * @property {number} totalTicketsSold
 * @property {Date} createdAt
 * @property {string} [email]
 * @property {string} [phone]
 */

export const organizerTypes = {
  Organizer,
  OrganizerPublic,
  SocialLinks,
  BrandingColors,
  NotificationPreferences,
  OrganizerStats,
};
