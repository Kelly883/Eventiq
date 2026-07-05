export const formatUtilizationPercentage = (used, total) => {
  if (!total) return '0%';
  return `${Math.round((used / total) * 100)}%`;
};

export const calculateLowStock = (inventory, threshold = 10) => {
  return inventory.remaining <= threshold;
};
