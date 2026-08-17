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
 * @property {number|null} peakSalesHour
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
 * @param {string} [currency='USD']
 * @returns {string}
 */
export function formatRevenue(metrics, currency = 'NGN') {
  if (!metrics || metrics.totalRevenue == null) return new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency,
  }).format(0);
  return new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency,
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
 * @param {AnalyticsMetrics} [metrics]
 * @param {'up'|'down'|'flat'} [direction]
 * @returns {'up'|'down'|'flat'}
 */
export function getTrendIndicator(metrics, direction = 'flat') {
  if (!metrics) return 'flat';
  return direction;
}

/**
 * @param {import('./types').SalesVelocityDataPoint[]} dataPoints
 * @returns {import('./types').SalesVelocityDataPoint[]}
 */
export function buildSalesVelocityData(dataPoints) {
  if (!dataPoints || dataPoints.length === 0) return [];
  
  const sorted = [...dataPoints].sort((a, b) => new Date(a.timestamp) - new Date(b.timestamp));
  
  let cumulativeSales = 0;
  let cumulativeRevenue = 0;
  
  return sorted.map(point => {
    cumulativeSales += point.periodSales;
    cumulativeRevenue += point.periodRevenue;
    
    return {
      timestamp: point.timestamp,
      cumulativeSales,
      cumulativeRevenue,
      periodSales: point.periodSales,
      periodRevenue: point.periodRevenue,
    };
  });
}


