import React from 'react';

export interface AvailabilityBadgeProps {
  ticketsAvailable: number | null;
  ticketsTotal: number;
}

const AvailabilityBadge = ({ ticketsAvailable, ticketsTotal }: AvailabilityBadgeProps) => {
  if (ticketsTotal === 0 || (ticketsAvailable != null && ticketsAvailable <= 0)) {
    return (
      <span className="availability-badge availability-badge--sold-out">
        Sold Out
      </span>
    );
  }

  const percentAvailable = (ticketsAvailable != null ? ticketsAvailable : ticketsTotal) / ticketsTotal * 100;
  const isPlenty = percentAvailable > 20;
  const isLow = percentAvailable >= 5 && percentAvailable <= 20;

  const classes = 'availability-badge';
  const additionalClass = isPlenty ? 'availability-badge--green' : isLow ? 'availability-badge--amber' : 'availability-badge--red';

  const text = isPlenty
    ? `${ticketsAvailable != null ? ticketsAvailable : ticketsTotal} tickets left`
    : isLow
      ? `${ticketsAvailable != null ? ticketsAvailable : ticketsTotal} tickets left`
      : 'Sold Out';

  return (
    <span className={`${classes} ${additionalClass}`}>
      {text}
    </span>
  );
};

export default AvailabilityBadge;