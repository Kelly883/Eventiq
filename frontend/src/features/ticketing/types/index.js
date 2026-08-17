/**
 * @typedef {Object} TicketTier
 * @property {number} id
 * @property {number} event_id
 * @property {string} name
 * @property {string} description
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

import {
  isEarlyBirdActiveForTier,
  getEffectivePriceForTier,
  isAvailableForTier,
  getRemainingQuantity,
  formatSalesWindow,
  isSalesWindowActive,
  validateSalesWindowDates,
  normalizeSalesDate,
} from '../../lib/dateUtils';

/**
 * @param {TicketTier} tier
 * @returns {boolean}
 */
export function isEarlyBirdActive(tier) {
  return isEarlyBirdActiveForTier(tier);
}

/**
 * @param {TicketTier} tier
 * @returns {number}
 */
export function getEffectivePrice(tier) {
  return getEffectivePriceForTier(tier);
}

/**
 * @param {TicketTier} tier
 * @returns {boolean}
 */
export function isAvailable(tier) {
  return isAvailableForTier(tier);
}

/**
 * @param {TicketTier} tier
 * @returns {number|null}
 */
export function getRemainingQuantity(tier) {
  return getRemainingQuantity(tier);
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
