/**
 * @typedef {Object} TicketTier
 * @property {number} id
 * @property {number} event_id
 * @property {string} name
 * @property {string|null} description
 * @property {number} price
 * @property {number|null} early_bird_price
 * @property {string|null} early_bird_end_date
 * @property {number} min_purchase
 * @property {number|null} max_purchase
 * @property {number} quantity
 * @property {number} sold_count
 * @property {number|null} available_count
 * @property {number|null} max_per_customer
 * @property {string|null} benefits_description
 * @property {string|null} tier_image_url
 * @property {string|null} sales_start_date
 * @property {string|null} sales_end_date
 * @property {boolean} is_active
 * @property {number} tier_order
 * @property {'draft' | 'published' | 'archived'} status
 * @property {string} currency
 * @property {string|null} voucher_code
 * @property {string|null} sales_channel
 * @property {string|null} published_at
 * @property {number|null} created_by
 * @property {number|null} updated_by
 * @property {string} created_at
 * @property {string} updated_at
 */

/**
 * @typedef {Object} Event
 * @property {number} id
 * @property {number|null} user_id
 * @property {number} organizer_id
 * @property {string} title
 * @property {string} description
 * @property {string|null} banner_image_url
 * @property {string} start_datetime
 * @property {string} end_datetime
 * @property {string} venue_name
 * @property {string|null} venue_address
 * @property {number|null} latitude
 * @property {number|null} longitude
 * @property {number} capacity
 * @property {'draft' | 'published' | 'archived'} status
 * @property {string|null} category
 * @property {TicketTier[]} ticket_tiers
 * @property {string} created_at
 * @property {string} updated_at
 */

export const eventTypes = { Event, TicketTier };
