import React from 'react';
import { format } from 'date-fns';
import { Event, Availability } from '../../types';
import AvailabilityIndicator from './AvailabilityIndicator';
import { useAuthContext } from '../../../auth/context/AuthContext';

const TRUNCATE_LINES = 2;
const LINE_HEIGHT = 1.2;

const getTruncatedTitle = (title: string, maxLines = TRUNCATE_LINES): string => {
  if (title.length <= 40) return title;
  const trimmed = title.slice(0, 40);
  return trimmed + '...';
};

// startTime/endTime are stored as "HH:mm:ss" strings; render them in a
// readable 12-hour format.
const formatTime = (timeString?: string | null): string => {
  if (!timeString) return '';
  try {
    return format(new Date(`1970-01-01T${timeString}Z`), 'h:mm a');
  } catch {
    return timeString;
  }
};

const CardSkeleton = (): JSX.Element => (
  <div className="h-48 rounded bg-slate-100 animate-pulse" aria-hidden="true" />
);

// Enhanced price formatting with currency support
const formatPrice = (price: number, currency: string = 'EUR'): string => {
  if (!price && price !== 0) return 'Free';
  return `from €${price.toFixed(2)}`;
};

const EventCard = ({ event, onClick, onAddToCart }: { event: Event; onClick: () => void; onAddToCart?: (event: Event) => void }) => {
  const {
    title,
    organizer,
    startTime,
    endTime,
    venue,
    bannerUrl,
    ticketTiers,
    currency,
  } = event;

  const organizerName = organizer.name;

  const ticketsAvailable = ticketTiers?.reduce(
    (sum, tier) => sum + (tier.ticketsAvailable ?? 0),
    0
  ) ?? 0;

  const totalTickets = ticketTiers?.reduce(
    (sum, tier) => sum + (tier.totalTickets ?? 0),
    0
  ) ?? 0;

  const availability =
    totalTickets > 0
      ? ticketsAvailable <= 0
        ? 'sold_out'
        : ticketsAvailable / totalTickets <= 0.2
        ? 'low'
        : 'available'
      : 'sold_out';

  const ticketsRemaining = ticketsAvailable;

  const price = ticketTiers?.[0]?.currentPrice ?? 0;
  const ticketTier = ticketTiers?.[0];

  const [isMouseOver, setIsMouseOver] = React.useState(false);
  const [imageFailed, setImageFailed] = React.useState(false);
  const [isTouching, setIsTouching] = React.useState(false);
  const { user, isAuthenticated } = useAuthContext();

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      onClick();
    }
  };

  const handleTouchStart = () => {
    setIsTouching(true);
  };

  const handleTouchEnd = () => {
    setIsTouching(false);
  };

  const handleImageError = () => {
    setImageFailed(true);
  };

  // Show skeleton when no event data available
  if (!event) {
    return <CardSkeleton />;
  }

  // Handle Add to Cart with auth check
  const handleAddToCart = () => {
    if (isAuthenticated) {
      onAddToCart?.(event);
    } else {
      // Navigate to login or show prompt
      window.dispatchEvent(
        new CustomEvent('eventiq:redirect-login', { detail: { source: 'event-card-add-to-cart' } })
      );
    }
  };

  return (
    <div
      className="group relative rounded-lg overflow-hidden border border-gray-200 bg-white cursor-pointer transition-all duration-200 hover:shadow-md hover:shadow-gray-200/20 min-h-[280px]"
      onClick={onClick}
      onKeyDown={handleKeyDown}
      role="button"
      tabIndex={0}
      aria-label={`${title} event by ${organizerName}, ${formatTime(startTime)} to ${formatTime(endTime)}`}
      onMouseOver={() => setIsMouseOver(true)}
      onMouseOut={() => setIsMouseOver(false)}
      onTouchStart={handleTouchStart}
      onTouchEnd={handleTouchEnd}
    >
      <div className="relative h-48">
        <img
          src={imageFailed ? '/placeholder-event.jpg' : (bannerUrl || '/placeholder-event.jpg')}
          alt={title}
          className="absolute inset-0 w-full h-full object-cover transition-opacity group-hover:opacity-80"
          onError={handleImageError}
        />
        <div
          className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 via-black/40 to-transparent p-3"
        >
          <h3
            className={`line-clamp-2 text-white text-sm font-medium ${isMouseOver ? 'text-primary-400' : 'text-white'}`}
            style={{ lineHeight: `${LINE_HEIGHT}em` }}
            title={title}
          >
            {getTruncatedTitle(title)}
          </h3>
          <p className="text-xs text-white/80 mt-1" title={organizerName}>{organizerName}</p>
        </div>
        <div className="absolute top-3 left-3">
          <AvailabilityIndicator
            availability={availability}
            ticketsRemaining={ticketsRemaining}
          />
        </div>
      </div>

      <div className="p-3 flex flex-col gap-2">
        <div className="flex flex-wrap items-center justify-between gap-2 text-sm text-gray-600">
          <span>{venue?.name || 'Unknown venue'}</span>
          <span>{formatTime(startTime)} to {formatTime(endTime)}</span>
        </div>

        {/* Price section - now more prominent */}
        {ticketTier && (
          <div className={`text-base font-medium text-gray-800 ${isTouching ? 'text-primary-600' : 'text-gray-600'}`}>
            {formatPrice(price ?? 0, currency ?? 'EUR')}
          </div>
        )}

        {/* Add to Cart CTA - prominent on mobile, with auth check */}
        {onAddToCart && (
          <button
            onClick={handleAddToCart}
            className={`w-full px-4 py-2 rounded-md font-medium transition-colors ${isTouching ? 'bg-primary-100' : 'hover:bg-primary-100 hover:text-primary-700'}`}
            aria-label="Add to cart"
            title="Add to cart"
          >
            <svg className="inline-block mr-1.5 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
              Add to Cart
            </svg>
            Add to Cart
          </button>
        )}
      </div>
    </div>
  );
};

export default React.memo(EventCard);

export default React.memo(EventCard);