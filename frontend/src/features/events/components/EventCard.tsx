import React from 'react';
import type { Event } from '../types/shared';

export interface EventCardProps {
  event: Event;
  onClick?: (event: Event) => void;
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
