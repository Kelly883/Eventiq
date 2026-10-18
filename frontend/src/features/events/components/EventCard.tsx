import React from 'react';
import type { Event } from '../types/shared';

export interface EventCardProps {
  event: Event;
  onClick?: (event: Event) => void;
  onFavoriteToggle?: (eventId: number) => void;
  isFavorite?: boolean;
  loading?: boolean;
}

const fallbackImage =
  'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=600&h=400&fit=crop';

function formatDate(dateString: string | null | undefined) {
  if (!dateString) return 'Date TBA';
  const date = new Date(dateString);
  if (Number.isNaN(date.getTime())) return 'Date TBA';
  return date.toLocaleDateString('en-US', {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
  });
}

function formatTime(dateString: string | null | undefined) {
  if (!dateString) return '';
  const date = new Date(dateString);
  if (Number.isNaN(date.getTime())) return '';
  return date.toLocaleTimeString('en-US', {
    hour: 'numeric',
    minute: '2-digit',
    hour12: true,
  });
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
  if (price == null) return 'Free';
  const num = typeof price === 'string' ? parseFloat(price) : price;
  if (Number.isNaN(num)) return 'Free';
  const symbol = getCurrencySymbol(currency);
  return `${symbol}${num.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`;
}

const EventCard = ({
  event,
  onClick,
  onFavoriteToggle,
  isFavorite = false,
  loading = false,
}: EventCardProps) => {
  const [hover, setHover] = React.useState(false);
  const eventImageRef = React.useRef<HTMLImageElement>(null);

  const eventAny = event as unknown as Record<string, unknown>;
  const imageUrl =
    (eventAny.banner_image_url as string) ||
    (eventAny.image_url as string) ||
    fallbackImage;
  const venue =
    (eventAny.venue_name as string) ||
    (eventAny.venue as string) ||
    'Venue TBA';
  const organizerRaw = eventAny.organizer;
  const organizerName =
    typeof organizerRaw === 'string'
      ? organizerRaw
      : organizerRaw &&
        typeof organizerRaw === 'object' &&
        'name' in organizerRaw
      ? (organizerRaw as { name?: string | null }).name ||
        'Unknown Organizer'
      : 'Unknown Organizer';
  const startDateTime =
    (eventAny.start_datetime as string) ||
    (eventAny.start_date as string);
  const price =
    eventAny.ticket_price != null ? Number(eventAny.ticket_price) : null;
  const currency = (eventAny.currency as string) || 'NGN';
  const ticketsRemaining =
    (eventAny.tickets_remaining as number) ??
    (eventAny.ticketsRemaining as number);
  const totalCapacity =
    (eventAny.capacity as number) ??
    (eventAny.total_capacity as number) ??
    0;

  const percentAvailable =
    ticketsRemaining != null && totalCapacity > 0
      ? (ticketsRemaining / totalCapacity) * 100
      : 100;

  let availabilityStatus: 'plenty' | 'low' | 'sold-out';
  let availabilityText: string;
  if (
    totalCapacity === 0 ||
    (ticketsRemaining != null && ticketsRemaining <= 0)
  ) {
    availabilityStatus = 'sold-out';
    availabilityText = 'Sold Out';
  } else if (percentAvailable > 20) {
    availabilityStatus = 'plenty';
    availabilityText = `${ticketsRemaining != null ? ticketsRemaining : 'Many'} tickets left`;
  } else {
    availabilityStatus = 'low';
    availabilityText = `${ticketsRemaining != null ? ticketsRemaining : 'Few'} tickets left`;
  }

  const isTrending = Array.isArray(eventAny.ticket_tiers)
    ? (eventAny.ticket_tiers as Array<{ is_visible?: boolean; is_sold_out?: boolean }>).some(
        (t) => t.is_visible && !t.is_sold_out,
      )
    : false;

  const handleCardClick = () => {
    onClick?.(event);
  };

  const handleImageError = () => {
    if (eventImageRef.current) {
      eventImageRef.current.src = fallbackImage;
      eventImageRef.current.alt = event.title || 'Event preview';
    }
  };

  if (loading) {
    return (
      <article
        className="event-card skeleton-card"
        aria-hidden="true"
      >
        <div className="skeleton-image-lg" />
        <div className="skeleton-title-lg" />
        <div className="skeleton-text-sm" />
        <div className="skeleton-text-xs" />
        <div className="skeleton-text-sm" />
      </article>
    );
  }

  return (
    <article
      className="event-card"
      onClick={handleCardClick}
      onMouseEnter={() => setHover(true)}
      onMouseLeave={() => setHover(false)}
      role="button"
      tabIndex={0}
      aria-label={`Event: ${event.title}`}
    >
      <div className="event-image-container">
        <img
          ref={eventImageRef}
          src={imageUrl}
          alt={event.title || 'Event preview'}
          className="event-image"
          loading="lazy"
          onError={handleImageError}
        />
        {event.category && (
          <span className="event-badge category">{event.category}</span>
        )}
        {isTrending && (
          <span className="event-badge trending">Trending</span>
        )}
        <button
          className="event-favorite-btn"
          onClick={() => onFavoriteToggle?.(event.id)}
          aria-label={isFavorite ? 'Remove from favorites' : 'Add to favorites'}
          title={isFavorite ? 'Remove from favorites' : 'Add to favorites'}
        >
          <svg
            width="18"
            height="18"
            viewBox="0 0 24 24"
            fill={isFavorite ? '#e83e8c' : 'none'}
            stroke={isFavorite ? '#e83e8c' : 'currentColor'}
            strokeWidth="2"
            aria-hidden="true"
          >
            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 1.5 9.15.82 7.5 1.5 3.75 6.34 1.5 2l.75-.75C3.51 7.34 5.77 8.5 8 8.5c.77 0 1.53-.2 2.29-.61l2.18 1.89 1.25-2.03a.995.995 0 01.96.69l-1.5 1.25 2.7 2.19 1.55-1.13.75.57a3.12 3.12 0 014.23 2.22l-1.89 2.15z" />
          </svg>
        </button>
      </div>
      <div className="event-details">
        <h3 className="event-title">{event.title || 'Untitled Event'}</h3>
        <div className="event-meta">
          <div className="meta-row meta-row-date">
            <svg
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              strokeWidth="2"
              aria-hidden="true"
            >
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
              <line x1="16" y1="2" x2="16" y2="6" />
              <line x1="8" y1="2" x2="8" y2="6" />
              <line x1="3" y1="10" x2="21" y2="10" />
            </svg>
            <span>{formatDate(startDateTime)} {formatTime(startDateTime)}</span>
          </div>
          <div className="meta-row">
            <svg
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              strokeWidth="2"
              aria-hidden="true"
            >
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z" />
              <circle cx="12" cy="10" r="3" />
            </svg>
            <span>{venue}</span>
          </div>
        </div>
        <div className="event-footer">
          <div className="event-price">
            <span className="price-label">From</span>
            <span className="price-value">
              {price != null ? formatPrice(price, currency) : 'Free'}
            </span>
          </div>
          <span className={`availability-badge availability-badge--${availabilityStatus === 'plenty' ? 'green' : availabilityStatus === 'low' ? 'amber' : 'red'}`}>
            {availabilityText}
          </span>
          {event.ticket_tiers && event.ticket_tiers.length > 0 && (
            <div className="event-rating">
              <span className="event-rating-stars">
                {Array(5)
                  .fill(0)
                  .map((_, i) => (
                    <svg
                      key={i}
                      width="14"
                      height="14"
                      viewBox="0 0 24 24"
                      fill={i < (event.rating ?? 0) ? '#f6e05e' : '#e5e5e5'}
                      stroke="currentColor"
                      strokeWidth="2"
                      aria-hidden="true"
                    >
                      <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 7.93L12 17.77l-6.18 3.21L5 12.14 2 9.27l6.9-1.53L12 2z" />
                    </svg>
                  ))}
              </span>
              <span className="event-rating-count">{event.ratingCount || 0} reviews</span>
            </div>
          )}
        </div>
      </div>
      {onClick && (
        <button
          type="button"
          onClick={(e) => {
            e.stopPropagation();
            onClick(event);
          }}
          className="btn-view-event"
          aria-label={`View details for ${event.title}`}
        >
          View Event
        </button>
      )}
    </article>
  );
};

export default React.memo(EventCard);
