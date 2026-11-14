import React, { useContext, useState } from 'react';
import { useCartContext } from '../../../features/checkout/context/CartContext';
import AvailabilityBadge from './AvailabilityBadge';
import type { SharedTicketTier } from '../types/shared';

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

export interface TicketTierCardProps {
  tier: SharedTicketTier;
  onSelect?: () => void;
  loading?: boolean;
}

const TicketTierCard = ({
  tier,
  onSelect,
  loading = false,
}: TicketTierCardProps) => {
  const { cart, addToCart } = useCartContext();
  const [added, setAdded] = useState(false);
  const [showToast, setShowToast] = useState(false);

  if (!tier) return null;

  const name = tier.name || 'General Admission';
  const price = tier.price;
  const currency = tier.currency;
  const quantity = tier.quantity;
  const soldCount = tier.sold_count ?? 0;
  const availableCount = tier.available_count ?? (quantity != null ? quantity - soldCount : null);
  const isSoldOut = availableCount != null ? availableCount <= 0 : false;

  const cartItemCount = cart.reduce((sum, item) => sum + (item.quantity ?? 1), 0);

  const handleSelect = async () => {
    if (isSoldOut || !onSelect) return;

    setAdded(true);
    setShowToast(true);

    // Add to cart with optimistic UI
    if (onSelect) {
      await onSelect();
    }

    // Add to cart context
    if (addToCart) {
      await addToCart({
        ticketTierId: tier.id || name,
        quantity: 1,
        unitPrice: price || 0,
        eventTitle: tier.eventTitle || name,
        eventDate: tier.eventDate || '',
        eventVenue: tier.eventVenue || '',
        tierName: name,
        currency,
      });
    }

    // Reset added state after toast
    setTimeout(() => setAdded(false), 2000);
  };

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
            onClick={handleSelect}
            disabled={isSoldOut || !onSelect}
            className={`ticket-tier-card__select-button ${added ? 'opacity-80' : ''}`}
            aria-disabled={isSoldOut || !onSelect}
            aria-label={isSoldOut ? 'Sold out' : `Add ${name} ticket to cart`}
          >
            {showToast ? (
              <span>Added to cart!</span>
            ) : (
              <span>{selectedCount ? `${selectedCount} Selected` : 'Select Ticket'}</span>
            )}
            {cartItemCount > 0 && (
              <span className="cart-badge">${cartItemCount}</span>
            )}
          </button>
        )}
      </div>
    </div>
  );
};

export default React.memo(TicketTierCard);
