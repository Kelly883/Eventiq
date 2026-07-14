import React from 'react';
import { AVAILABILITY_COLORS } from '../../utils/dateConstants';

const LABELS = {
  available: 'Available',
  lowStock: 'Low stock',
  soldOut: 'Sold out',
};

/**
 * Shows ticket availability as color + text + shape (not color alone),
 * per WCAG 1.4.1 (use of color). Screen readers get the label via
 * visible text, not just an aria-label on a colored dot.
 */
const AvailabilityIndicator = ({ status = 'available' }) => {
  const color = AVAILABILITY_COLORS[status] ?? AVAILABILITY_COLORS.available;
  const label = LABELS[status] ?? LABELS.available;

  return (
    <span className="inline-flex items-center gap-1.5 text-sm">
      <span
        aria-hidden="true"
        style={{ backgroundColor: color }}
        className="inline-block h-2.5 w-2.5 rounded-full shrink-0"
      />
      <span>{label}</span>
    </span>
  );
};

export default AvailabilityIndicator;
