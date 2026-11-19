import React, { useState, useEffect } from 'react';
import { useSearchParams, useLocation } from 'react-router-dom';
import { format } from 'date-fns';
import { Event, Availability } from '../../types';
import AvailabilityIndicator from './AvailabilityIndicator';
import { useAuthContext } from '../../../auth/context/AuthContext';
import { FaHeart, FaShare, FaThumbtack } from 'react-icons/fa';

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

interface EventCardProps {
  event: Event;
  onClick: () => void;
  onAddToCart?: (event: Event) => void;
  onFavorite?: (event: Event) => void;
  onShare?: (event: Event) => void;
  onWishlist?: (event: Event) => void;
  isWished?: boolean;
  wishlistUrl?: string;
}

const EventCard = ({ event, onClick, onAddToCart, onFavorite, onShare, onWishlist, isWished, wishlistUrl }: EventCardProps) => {
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
  const [isFavorite, setIsFavorite] = React.useState(false);
  const [isWished, setIsWished] = React.useState(false);
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
          className="absolute inset-0 w-full h-full object-cover transition-opacity group-hover:opacity-80 cursor-pointer"
          onError={handleImageError}
          onClick={() => window.open(bannerUrl || "#", "_blank")}
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

        {/* Action Buttons - Favorite, Share, Wishlist */}
        {isAuthenticated && (
          <div className="flex flex-wrap items-center justify-between gap-2 pt-2">
            <button
              onClick={() => setIsFavorite((prev) => !prev)}
              className={`p-1 rounded hover:bg-gray-100 transition-colors ${
                isFavorite ? 'text-red-500' : ''
              }`}
              aria-label={isFavorite ? 'Remove from favorites' : 'Add to favorites'}
              title={isFavorite ? 'Remove from favorites' : 'Add to favorites'}
            >
              <svg
                className={`w-4 h-4 ${
                  isFavorite ? 'text-red-500 fill-red-500' : 'currentColor'
                }`}
                fill={isFavorite ? 'red' : 'currentColor'}
                viewBox="0 0 24 24"
                aria-hidden="true"
              >
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.68l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.32 1.32L12 21.28l7.73-7.73a5.5 5.5 0 0 0 0-7.78l-1.32-1.32L12 4.38a5.5 5.5 0 0 0-7.78 0zM12 15.93a3.37 3.37 0 0 0 0 6.74 3.37 3.37 0 0 0 6.74 0 3.37 3.37 0 0 0-6.74 0z" />
              </svg>
            </button>
            <button
              onClick={() => onShare?.(event)}
              className="p-1 rounded hover:bg-gray-100 transition-colors"
              aria-label="Share event"
              title="Share event"
            >
              <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M13.5 13.5L19.5 19.5M19.5 4.5L4.5 19.5M13.5 13.5L16.5 16.5" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" />
              </svg>
            </button>
            <button
              onClick={() => setIsWished((prev) => !prev)}
              className={`p-1 rounded hover:bg-gray-100 transition-colors ${
                isWished ? 'text-indigo-600' : ''
              }`}
              aria-label={isWished ? 'Remove from wishlist' : 'Add to wishlist'}
              title={isWished ? 'Remove from wishlist' : 'Add to wishlist'}
            >
              <svg
                className={`w-4 h-4 ${
                  isWished ? 'text-indigo-600 fill-indigo-600' : 'currentColor'
                }`}
                fill={isWished ? 'indigo' : 'currentColor'}
                viewBox="0 0 24 24"
                aria-hidden="true"
              >
                <path d="M17 8l-1.41 1.41L14 10.83l-1.98 1.98L7 15.83l-1.41 1.41L11 9.59l1.98 1.98L7 15.83 11 17.83l1.41-1.41L11 9.59 17 3z" />
              </svg>
            </button>
          </div>
        )}
      </div>
    </div>
  );
};

    window.dispatchEvent(
      new CustomEvent('eventiq:event-id-changed', {
        detail: { eventId: eventId },
      })
    );
  }
}, [useSearchParams()]);

export default React.memo(EventCard);

    window.dispatchEvent(
      new CustomEvent('eventiq:event-id-changed', {
        detail: { eventId: eventId },
      })
    );
  }
}, [useSearchParams()]);

