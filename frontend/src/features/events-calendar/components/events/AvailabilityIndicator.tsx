import React from 'react';
import { AVAILABILITY_COLORS } from '../../utils/dateConstants';

const LABELS = {
  available: 'Available',
  low: 'Low stock',
  sold_out: 'Sold out',
};

const AvailabilityIndicator = ({
  availability = 'available',
  ticketsRemaining,
}: {
  availability: 'available' | 'low' | 'sold_out';
  ticketsRemaining?: number;
}) => {
  const color = AVAILABILITY_COLORS[availability] ?? AVAILABILITY_COLORS.available;
  const label =
    availability === 'available'
      ? `${ticketsRemaining ?? ''} tickets remaining`
      : availability === 'low'
      ? `${ticketsRemaining ?? 'few'} tickets remaining`
      : 'Sold Out';

  return (
    <span className="inline-flex items-center gap-1.5 text-sm">
      <span
        aria-hidden="true"
        style={{ backgroundColor: color }}
        className="inline-block h-2.5 w-2.5 rounded-full shrink-0"
        aria-label={label}
      />
      <span>{label}</span>
    </span>
  );
};

export default AvailabilityIndicator;