import React from 'react';
import AvailabilityBadge from './AvailabilityBadge';
import type { SharedTicketTier } from '../types/shared';

export interface TicketTierCardProps {
  tier: SharedTicketTier;
  onSelect?: () => void;
  loading?: boolean;
}

function getCurrencySymbol(currency?: string | null): string {
  const symbols: Record<string, string> = {
    USD: '$',
    EUR: '€',
    GBP: '£',
    NGN: '₦',
    GHS: '₵',
    KES: 'KSh',
    ZAR: 'R',
  };
  return symbols[(currency || 'USD').toUpperCase()] || '₦';
}

function formatPrice(
  price: number | string | null | undefined,
  currency?: string | null,
): string {
  if (price == null) return '—';
  const num = typeof price === 'string' ? parseFloat(price) : price;
  if (Number.isNaN(num)) return '—';
  const symbol = getCurrencySymbol(currency);
  return `${symbol}${num.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`;
}

const TicketTierCard = ({
  tier,
  onSelect,
  loading = false,
}: TicketTierCardProps) => {
  if (!tier) return null;

  const name = tier.name || 'General Admission';
  const price = tier.price;
  const currency = tier.currency;
  const quantity = tier.quantity;
  const soldCount = tier.sold_count ?? 0;
  const availableCount = tier.available_count ?? (quantity != null ? quantity - soldCount : null);
  const isSoldOut = availableCount != null ? availableCount <= 0 : false;

  return (
    <div
      className="ticket-tier-card"
      style={{ flexShrink: 0 }}
      role="button"
      tabIndex={0}
      aria-label={`${name} ticket tier, ${formatPrice(price, currency)}`}
    >
      <div className="ticket-tier-card__info">
        <span className="ticket-tier-card__name">{name}</span>
        <span className="ticket-tier-card__price">{formatPrice(price, currency)}</span>
      </div>
      {quantity != null && availableCount != null && (
        <div className="ticket-tier-card__availability">
          <AvailabilityBadge ticketsAvailable={availableCount} ticketsTotal={quantity} />
        </div>
      )}
      <div className="ticket-tier-card__select">
        {loading ? (
          <span className="ticket-tier-card__select-loading">Selecting...</span>
        ) : (
          <button
            type="button"
            onClick={onSelect}
            disabled={isSoldOut || !onSelect}
            className="ticket-tier-card__select-button"
            aria-disabled={isSoldOut || !onSelect}
          >
            Select Ticket
          </button>
        )}
      </div>
    </div>
  );
};

export default TicketTierCard;
