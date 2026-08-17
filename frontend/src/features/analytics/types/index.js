/**
 * @typedef {Object} AnalyticsMetrics
 * @property {string} id
 * @property {string} eventId
 * @property {string} organizerId
 * @property {number} totalRevenue
 * @property {number} totalTicketsSold
 * @property {number} totalPageViews
 * @property {number} totalTicketPageViews
 * @property {number} conversionRate
 * @property {number} averageTicketPrice
 * @property {string|null} peakSalesHour
 * @property {string|null} topTicketTier
 * @property {string|null} lastUpdatedAt
 * @property {string} createdAt
 * @property {string} updatedAt
 */

/**
 * @typedef {Object} SalesTimelineEntry
 * @property {string} id
 * @property {string} eventId
 * @property {string} ticketTierId
 * @property {string|null} pricingWindowId
 * @property {string} saleTimestamp
 * @property {number} quantity
 * @property {number} unitPrice
 * @property {number} totalAmount
 * @property {string|null} buyerEmail
 * @property {string|null} source
 * @property {string} createdAt
 */

/**
 * @typedef {Object} TierPerformance
 * @property {string} id
 * @property {string} eventId
 * @property {string} ticketTierId
 * @property {number} totalSold
 * @property {number} totalRevenue
 * @property {number} averagePrice
 * @property {number} percentageOfTotalSales
 * @property {number} percentageOfTotalRevenue
 * @property {number} conversionRate
 * @property {string|null} lastUpdatedAt
 * @property {string} createdAt
 * @property {string} updatedAt
 */

/**
 * @typedef {Object} SalesVelocityDataPoint
 * @property {string} timestamp
 * @property {number} cumulativeSales
 * @property {number} cumulativeRevenue
 * @property {number} periodSales
 * @property {number} periodRevenue
 */

/**
 * @param {AnalyticsMetrics} metrics
 * @returns {string}
 */
export function formatRevenue(metrics) {
  if (!metrics || metrics.totalRevenue == null) return '$0.00';
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(metrics.totalRevenue);
}

/**
 * @param {AnalyticsMetrics} metrics
 * @returns {string}
 */
export function formatConversionRate(metrics) {
  if (!metrics || metrics.conversionRate == null) return '0.0%';
  return metrics.conversionRate.toFixed(1) + '%';
}

/**
 * @param {AnalyticsMetrics} metrics
 * @param {'up'|'down'|'flat'} [direction]
 * @returns {'up'|'down'|'flat'}
 */
export function getTrendIndicator(metrics, direction = 'flat') {
  if (!metrics) return 'flat';
  return direction;
}


