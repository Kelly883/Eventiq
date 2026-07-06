// Type definitions for payouts feature

/**
 * @typedef {Object} Payout
 * @property {number} id
 * @property {number} organizer_id
 * @property {number} event_id
 * @property {number} amount
 * @property {string} currency
 * @property {string} status
 * @property {string} payout_method
 * @property {string} [transaction_id]
 * @property {Date} [processed_at]
 * @property {Date} created_at
 * @property {Date} updated_at
 */

/**
 * @typedef {Object} PayoutCalculation
 * @property {number} id
 * @property {number} payout_id
 * @property {number} event_id
 * @property {number} total_revenue
 * @property {number} platform_fee
 * @property {number} organizer_share
 * @property {number} tax_amount
 * @property {Object} breakdown
 * @property {Date} created_at
 */

/**
 * @typedef {Object} SettlementPolicy
 * @property {number} id
 * @property {string} name
 * @property {string} description
 * @property {number} platform_fee_percentage
 * @property {string} payout_frequency
 * @property {number} minimum_payout_amount
 * @property {boolean} is_active
 * @property {Date} created_at
 * @property {Date} updated_at
 */

/**
 * @typedef {Object} PayoutSummary
 * @property {number} total_pending
 * @property {number} total_processed
 * @property {number} total_earned
 * @property {number} next_payout
 */

/**
 * @typedef {Object} FilterOptions
 * @property {string} [status]
 * @property {Date} [start_date]
 * @property {Date} [end_date]
 * @property {number} [event_id]
 */
