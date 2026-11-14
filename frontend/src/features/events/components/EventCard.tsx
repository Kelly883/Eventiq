import React, { useState, useRef, useEffect } from 'react';
import type { Event } from '../types/shared';
import { api } from '../../../lib/api';
import { getCategoryIcon } from '../utils/categoryIcons';
import AvailabilityIndicator from '../events/AvailabilityIndicator';

const fallbackImage =
  'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=600&h=400&fit=crop';

const CardSkeleton = ({ reducedMotion }: { reducedMotion?: boolean }) => (
  <div className="h-48 rounded bg-slate-100 animate-pulse" aria-hidden="true" />
);

// Price formatting helper
const formatPrice = (price: number | undefined | null, currency: string = 'EUR'): string => {
  if (price === undefined || price === null) return 'Price TBA';
  if (price === 0) return 'Free';
  return `${currency === 'NGN' ? '₦' : '€'}${price.toFixed(2)}`;
};

export interface EventCardProps {
  event: Event;
  onClick?: (event: Event) => void;
  onFavoriteToggle?: (eventId: number) => void;
  isFavorite?: boolean;
  loading?: boolean;
  onCheckFavorite?: (eventId: number) => Promise<boolean>;
  onAddToCart?: (event: Event) => void;
}

const EventCard = ({
  event,
  onClick,
  onFavoriteToggle,
  isFavorite = false,
  loading = false,
  onCheckFavorite,
  onAddToCart,
}: EventCardProps) => {
  const [hover, setHover] = useState(false);
  const [favoriteState, setFavoriteState] = React.useState(isFavorite);
  const [isToggling, setIsToggling] = useState(false);
  const [isTouching, setIsTouching] = useState(false);
  const eventImageRef = useRef<HTMLImageElement>(null);

  // Check favorite status on mount if callback provided
  React.useEffect(() => {
    if (onCheckFavorite && isFavorite === false) {
      onCheckFavorite(event.id).then(setFavoriteState).catch(() => {});
    }
  }, [event.id, onCheckFavorite]);

  // Sync with prop changes
  React.useEffect(() => {
    setFavoriteState(isFavorite);
  }, [isFavorite]);

  const handleFavoriteToggle = async (eventId: number) => {
    if (isToggling) return;

    const newState = !favoriteState;
    setFavoriteState(newState); // Optimistic update
    setIsToggling(true);

    try {
      if (newState) {
        await api.post('/favorites', { event_id: eventId });
      } else {
        await api.delete(`/favorites/${eventId}`);
      }
      onFavoriteToggle?.(eventId);
    } catch (error) {
      setFavoriteState(favoriteState); // Revert on error
      console.error('Failed to toggle favorite:', error);
    } finally {
      setIsToggling(false);
    }
  };

  const eventAny = event as unknown as Record<string, unknown>;

  // Extract ticket tier info for price/availability
  const ticketTier = event.ticketTiers?.[0];
  const price = ticketTier?.price;
  const currency = ticketTier?.currency;
  const ticketsAvailable = ticketTier?.available_count ?? event.ticketsAvailable;
  const totalTickets = ticketTier?.totalTickets ?? event?.totalTickets;
  const availability =
    totalTickets > 0
      ? ticketsAvailable <= 0
        ? 'sold_out'
        : ticketsAvailable / totalTickets <= 0.2
        ? 'low'
        : 'available'
      : 'sold_out';

  const imageUrl =
    (eventAny.banner_image_url as string) ||
    fallbackImage;

  const handleImageLoad = () => {
    if (eventImageRef.current) {
      eventImageRef.current.alt = event.title || 'Event preview';
    }
  };

  const handleImageError = () => {
    // Image failed to load, use fallback
  };

  // Hover preview state
  const [showPreview, setShowPreview] = useState(false);
  const previewTimeout = useRef<NodeJS.Timeout>();

  const showPreviewHandler = () => {
    clearTimeout(previewTimeout.current);
    previewTimeout.current = setTimeout(() => setShowPreview(true), 200);
  };

  const hidePreviewHandler = () => {
    clearTimeout(previewTimeout.current);
    previewTimeout.current = setTimeout(() => setShowPreview(false), 200);
  };

  const handleCardClick = (e: React.MouseEvent) => {
    e.stopPropagation();
    onClick?.(event);
  };

  // Show skeleton when loading
  if (loading) {
    return (
      <article
        className="event-card group relative rounded-lg overflow-hidden border border-gray-200 bg-white cursor-pointer transition-all duration-200 hover:shadow-md hover:shadow-gray-200/20 focus-visible:ring-2 focus-visible:ring-indigo-600 focus-visible:ring-offset-2 focus-visible:outline-none"
        role="button"
        tabIndex={0}
        aria-label={`Event: ${event.title}`}
      >
        <CardSkeleton reducedMotion={false} />
      </article>
    );
  }

  return (
    <article
      className="event-card group relative rounded-lg overflow-hidden border border-gray-200 bg-white cursor-pointer transition-all duration-200 hover:shadow-md hover:shadow-gray-200/20 focus-visible:ring-2 focus-visible:ring-indigo-600 focus-visible:ring-offset-2 focus-visible:outline-none"
      onClick={handleCardClick}
      onMouseEnter={showPreviewHandler}
      onMouseLeave={hidePreviewHandler}
      onKeyDown={(e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          handleCardClick(e);
        }
      }}
      role="button"
      tabIndex={0}
      aria-label={`Event: ${event.title}`}
    >
      <div className="event-image-container relative h-48 w-full">
        <img
          src={imageUrl}
          alt={event.title || 'Event preview'}
          className="event-image w-full h-full object-cover transition-opacity duration-300 group-hover:opacity-80"
          loading="lazy"
          onError={handleImageError}
          onLoad={handleImageLoad}
        />
        {showPreview && (
          <div className="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
            <span className="text-white text-sm">Preview</span>
          </div>
        )}
        <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 via-black/40 to-transparent p-3">
          <h3
            className="line-clamp-2 text-white text-sm font-medium"
            style={{ lineHeight: 1.2em }}
            title={event.title}
          >
            {event.title.length > 40 ? event.title.slice(0, 40) + '...' : event.title}
          </h3>
          <p className="text-xs text-white/80 mt-1" title={event.venue?.name || 'Unknown venue'}>{
            event.venue?.name || 'Unknown venue'
          }</p>
        </div>
        <div className="absolute top-3 left-3">
          {price !== undefined && (
            <span className="text-xs font-medium text-primary-600">
              {formatPrice(price, currency ?? 'EUR')}
            </span>
          )}
          {availability !== 'available' && (
            <AvailabilityIndicator
              availability={availability}
              ticketsRemaining={ticketsAvailable}
            />
          )}
        </div>
      </div>
      <div className="p-4 flex flex-col gap-2 min-h-[160px]">
        <span className="text-sm text-gray-700">{event.venue?.name || 'Unknown venue'}</span>
        <p className="text-xs text-gray-500">{event.date?.split('T')[0] || 'Date TBA'}</p>
        {/* Add to Cart CTA */}
        {onAddToCart && (
          <button
            onClick={(e) => {
              e.stopPropagation();
              onAddToCart(event);
            }}
            className={`w-full px-4 py-2 rounded-md font-medium transition-colors ${isTouching ? 'bg-primary-100 text-primary-800' : 'hover:bg-primary-100 hover:text-primary-700'}`
            }
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
      {onClick && (
        <button
          type="button"
          onClick={(e) => {
            e.stopPropagation();
            onClick(event);
          }}
          className="btn-view-event absolute bottom-2 right-2"
          aria-label={`View details for ${event.title}`}
        >
          View Event
        </button>
      )}
    </article>
  );
};

export default React.memo(EventCard);
