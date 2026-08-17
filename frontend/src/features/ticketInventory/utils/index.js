/**
 * @param {number} sold
 * @param {number} allocated
 * @returns {string}
 */
export function formatUtilizationPercentage(sold, allocated) {
  if (allocated <= 0) return '0%';
  return Math.round((sold / allocated) * 100) + '%';
}

/**
 * @param {number} available
 * @param {number} threshold
 * @returns {boolean}
 */
export function calculateLowStock(available, threshold) {
  return available <= threshold;
}

/**
 * @param {number} delta
 * @returns {string}
 */
export function formatQuantityDelta(delta) {
  const sign = delta >= 0 ? '+' : '';
  return sign + delta;
}
