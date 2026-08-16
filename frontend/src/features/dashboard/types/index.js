/**
 * @typedef {Object} DashboardOverview
 * @property {number} totalRevenue
 * @property {number} totalTicketsSold
 * @property {number} averageConversionRate
 * @property {number} upcomingEventCount
 * @property {number} totalEventCount
 * @property {'up'|'down'|'flat'} revenueTrend
 * @property {'up'|'down'|'flat'} ticketsTrend
 * @property {'up'|'down'|'flat'} conversionTrend
 * @property {string} lastUpdatedAt
 */

/**
 * @typedef {Object} EventMetrics
 * @property {string} id
 * @property {string} title
 * @property {string} date
 * @property {'draft'|'upcoming'|'live'|'past'} status
 * @property {string} thumbnailUrl
 * @property {number} totalRevenue
 * @property {number} totalTicketsSold
 * @property {number} ticketsAvailable
 * @property {number} utilizationPercentage
 * @property {number} conversionRate
 */

/**
 * @typedef {Object} EventDetail
 * @property {string} eventId
 * @property {string} title
 * @property {string} date
 * @property {'draft'|'upcoming'|'live'|'past'} status
 * @property {number} totalRevenue
 * @property {number} totalTicketsSold
 * @property {number} conversionRate
 * @property {Array} tiers
 * @property {Array} pricingWindows
 */

/**
 * @typedef {Object} ActivityItem
 * @property {string} id
 * @property {string} eventId
 * @property {string} eventName
 * @property {string} tierId
 * @property {string} tierName
 * @property {number} quantity
 * @property {number} unitPrice
 * @property {number} totalAmount
 * @property {string} saleTimestamp
 * @property {string|null} buyerEmail
 */

/**
 * @typedef {Object} DashboardPreferences
 * @property {string} defaultEventFilter
 * @property {string} defaultDateRange
 * @property {string|null} expandedEventId
 * @property {boolean} showActivityFeed
 * @property {boolean} autoRefreshEnabled
 */

export { DashboardOverview, EventMetrics, EventDetail, ActivityItem, DashboardPreferences };
