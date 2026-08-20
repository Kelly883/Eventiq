/**
 * @typedef {'manual_increase' | 'manual_decrease' | 'reallocation' | 'system_correction'} AdjustmentType
 */

/**
 * @typedef {Object} TicketInventory
 * @property {string} id
 * @property {string} eventId
 * @property {string} ticketTierId
 * @property {number} totalAllocated
 * @property {number} totalSold
 * @property {number} totalAvailable
 * @property {number} lowStockThreshold
 * @property {boolean} isLowStock
 * @property {string|null} lastUpdatedAt
 * @property {string} createdAt
 * @property {string} updatedAt
 */

/**
 * @typedef {Object} InventoryAdjustment
 * @property {string} id
 * @property {string} eventId
 * @property {string} ticketTierId
 * @property {string|null} pricingWindowId
 * @property {string} organizerId
 * @property {AdjustmentType} adjustmentType
 * @property {number} quantityBefore
 * @property {number} quantityAfter
 * @property {number} quantityDelta
 * @property {string} reason
 * @property {string} createdAt
 */

/**
 * @typedef {Object} TicketInventoryTierSummary
 * @property {string} tierId
 * @property {string} tierName
 * @property {number} allocated
 * @property {number} sold
 * @property {number} available
 * @property {boolean} isLowStock
 */

/**
 * @typedef {Object} InventorySummary
 * @property {number} totalCapacity
 * @property {number} totalSold
 * @property {number} totalAvailable
 * @property {number} utilizationPercentage
 * @property {number} lowStockTierCount
 * @property {TicketInventoryTierSummary[]} tiers
 */

export { AdjustmentType, TicketInventory, InventoryAdjustment, TicketInventoryTierSummary, InventorySummary };
