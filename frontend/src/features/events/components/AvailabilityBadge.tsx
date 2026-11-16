import React from 'react';

export interface AvailabilityBadgeProps {
  ticketsAvailable: number | null;
  ticketsTotal: number;
}

/**
 * Color-blind friendly availability badge with:
 * - Color fill (green/amber/red)
 * - SVG patterns as secondary differentiation for color-blind users
 * - Clear text labels for all states
 * - Proper ARIA labels for screen readers
 */

const PATTERNS = {
  available: 'M6 2l3 3L11 6L8 10L6 8L2 12L6 6zM6 2l3 3L11 6L8 10L6 8L2 12L6 6z',
  low: 'M6 2l3 3L11 6L8 10L6 8L2 12L6 6z',
  sold_out: 'M2 12l7-7 2 5-7 2-5-2zM6 2l3 3L11 6L8 10L6 8L2 12L6 6z',
};

const PATTERN_IMAGES = {
  available: 'data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' fill=\'none%3E%3Cpath d=\'M6 2l3 3L11 6L8 10L6 8L2 12L6 6zM6 2l3 3L11 6L8 10L6 8L2 12L6 6z\' fill=\'%2310b981\'/%3E%3C/svg%3E',
  low: 'data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' fill=\'none%3E%3Cpath d=\'M6 2l3 3L11 6L8 10L6 8L2 12L6 6z\' fill=\'%23f59e0b\'/%3E%3C/svg%3E',
  sold_out: 'data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' fill=\'none%3E%3Cpath d=\'M6 2l3 3L11 6L8 10L6 8L2 12L6 6z\' fill=\'%23ef4444\'/%3E%3C/svg%3E',
};

const AvailabilityBadge = ({ ticketsAvailable, ticketsTotal }: AvailabilityBadgeProps) => {
  if (ticketsTotal === 0 || (ticketsAvailable != null && ticketsAvailable <= 0)) {
    return (
      <span className="availability-badge availability-badge--sold-out" aria-label="Sold Out">
        <span aria-hidden="true" style={{ backgroundImage: PATTERN_IMAGES.sold_out }} />
        Sold Out
      </span>
    );
  }

  const percentAvailable = (ticketsAvailable != null ? ticketsAvailable : ticketsTotal) / ticketsTotal * 100;
  const isPlenty = percentAvailable > 20;
  const isLow = percentAvailable >= 5 && percentAvailable <= 20;

  const baseClasses = 'availability-badge';
  const colorClass = isPlenty ? 'availability-badge--green' : isLow ? 'availability-badge--amber' : 'availability-badge--red';
  const patternImage = PATTERN_IMAGES[(isPlenty ? 'available' : isLow ? 'low' : 'sold_out')];
  const ariaLabel = isPlenty
    ? `${ticketsAvailable != null ? ticketsAvailable : ticketsTotal} tickets available`
    : isLow
      ? `${ticketsAvailable != null ? ticketsAvailable : ticketsTotal} tickets remaining - low stock`
      : 'Sold Out';

  const text = isPlenty
    ? `${ticketsAvailable != null ? ticketsAvailable : ticketsTotal} tickets left`
    : isLow
      ? `${ticketsAvailable != null ? ticketsAvailable : ticketsTotal} tickets left - limited`
      : 'Sold Out';

  return (
    <span className={`${baseClasses} ${colorClass}`} aria-label={ariaLabel} style={{ backgroundImage: patternImage ? `url(${patternImage})` : undefined }}>
      {text}
    </span>
  );
};

export default AvailabilityBadge;