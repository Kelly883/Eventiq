import React from 'react';
import { AVAILABILITY_COLORS } from '../../utils/dateConstants';

const ICONS: Record<Availability, string> = {
  available: '✓',
  low: '⚠',
  sold_out: '✗',
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
    <span
      className="inline-flex items-center gap-1.5 text-sm"
      aria-label={label}
    >
      <span
        aria-hidden="true"
        style={{ backgroundColor: color }}
        className="inline-block h-2.5 w-2.5 rounded-full shrink-0"
      />
      <span className="font-medium" aria-hidden="true">
        {ICONS[availability]}
      </span>
      <span>{label}</span>
    </span>
  );
};

export default AvailabilityIndicator;