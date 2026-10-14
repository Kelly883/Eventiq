import React from 'react';
import AvailabilityBadge from './AvailabilityBadge';

export interface TicketTierCardProps {
  tier: import('../types/shared').TicketTier;
  onSelect?: () => void;
  loading?: boolean;
}

const TicketTierCard = ({ tier, onSelect, loading }: TicketTierCardProps) => {
  if (!tier) return null;

  const name = tier.name || 'General Admission';
  const price = tier.price;
  const quantity = tier.quantity;
  const soldCount = tier.sold_count || 0;
  const availableCount = tier.available_count ?? tier.quantity - tier.sold_count;
  const isSoldOut = tier.is_sold_out || (availableCount <= 0);

  return (
    <div className="ticket-tier-card" style={{ flexShrink: 0 }} role="button" tabIndex={0} aria-label={`${name} ticket tier, ₦${price.toLocaleString()}`}>
      <div className="ticket-tier-card__info">
        <span className="ticket-tier-card__name">{name}</span>
        <span className="ticket-tier-card__price">₦{price.toLocaleString()}</span>
      </div>
      <div className="ticket-tier-card__availability">
        {availableCount != null && (
          <AvailabilityBadge ticketsAvailable={availableCount} ticketsTotal={quantity} />
        )}
      </div>
      <div className="ticket-tier-card__select">
        {loading ? (
          <span className="ticket-tier-card__select-loading">Selecting...</span>
        ) : (
          <button
            type="button"
            onClick={onSelect}
            disabled={isSoldOut}
            className="ticket-tier-card__select-button"
            aria-disabled={isSoldOut}
          >
            Select Ticket
          </button>
        )}
      </div>
    </div>
  );
};

export default TicketTierCard;