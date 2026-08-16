/**
 * @typedef {Object} TicketTier
 * @property {number} id
 * @property {number} event_id
 * @property {string} name
 * @property {number} price
 * @property {number} quantity
 * @property {string|null} sales_start_date
 * @property {string|null} sales_end_date
 * @property {string|null} benefits_description
 * @property {string|null} tier_image_url
 * @property {number|null} early_bird_price
 * @property {string|null} early_bird_end_date
 * @property {number|null} max_per_customer
 * @property {string} created_at
 * @property {string} updated_at
 */

/**
 * @typedef {Object} Event
 * @property {number} id
 * @property {number} user_id
 * @property {number} organizer_id
 * @property {string} title
 * @property {string} description
 * @property {string|null} banner_image_url
 * @property {string} start_datetime
 * @property {string} end_datetime
 * @property {string} venue_name
 * @property {string} venue_address
 * @property {number} capacity
 * @property {'draft' | 'published' | 'archived'} status
 * @property {TicketTier[]} ticket_tiers
 * @property {string} created_at
 * @property {string} updated_at
 */

export const eventTypes = { Event, TicketTier };
