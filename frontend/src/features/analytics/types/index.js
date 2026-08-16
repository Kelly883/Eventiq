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

export { AnalyticsMetrics, SalesTimelineEntry, TierPerformance, SalesVelocityDataPoint };
