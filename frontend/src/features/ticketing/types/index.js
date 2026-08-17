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
 * @param {TicketTier} tier
 * @returns {boolean}
 */
export function isEarlyBirdActive(tier) {
  if (!tier.early_bird_price || !tier.early_bird_end_date) return false;
  return new Date() < new Date(tier.early_bird_end_date);
}

/**
 * @param {TicketTier} tier
 * @returns {number}
 */
export function getEffectivePrice(tier) {
  return isEarlyBirdActive(tier) ? tier.early_bird_price : tier.price;
}

/**
 * @param {TicketTier} tier
 * @returns {boolean}
 */
export function isAvailable(tier) {
  const now = new Date();
  if (tier.sales_start_date && now < new Date(tier.sales_start_date)) return false;
  if (tier.sales_end_date && now > new Date(tier.sales_end_date)) return false;
  return true;
}

/**
 * @param {number} price
 * @returns {string}
 */
export function formatPrice(price) {
  return new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
  }).format(price);
}
