/**
 * @typedef {Object} PricingWindow
 * @property {string} id
 * @property {string} event_id
 * @property {string} ticket_category_id
 * @property {string} window_name
 * @property {string} start_date_time
 * @property {string} end_date_time
 * @property {number} price
 * @property {number|null} quantity_limit
 * @property {number} quantity_sold
 * @property {boolean} is_active
 * @property {number} priority
 * @property {string} created_at
 * @property {string} updated_at
 */

/**
 * @typedef {Object} PricingWindowFormData
 * @property {string} window_name
 * @property {string} start_date_time
 * @property {string} end_date_time
 * @property {number} price
 * @property {number|null} quantity_limit
 * @property {number} priority
 */

/**
 * @typedef {Object} PricingPreviewData
 * @property {PricingWindow} window
 * @property {number} available_quantity
 * @property {boolean} is_active
 */

export { z } from 'zod';

export const pricingWindowSchema = z.object({
  window_name: z.string().min(1, 'Window name is required'),
  start_date_time: z.string().datetime(),
  end_date_time: z.string().datetime(),
  price: z.number().positive('Price must be greater than 0'),
  quantity_limit: z.number().int().positive('Quantity limit must be a positive integer').optional(),
  priority: z.number().int().min(0).optional(),
}).refine((data) => new Date(data.start_date_time) < new Date(data.end_date_time), {
  message: 'Start date must be before end date',
  path: ['start_date_time'],
});

export const pricingTypes = {
  PricingWindow,
  PricingWindowFormData,
  PricingPreviewData,
  pricingWindowSchema,
};
