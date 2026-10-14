import React from 'react';
import { showToast } from '../../../lib/api';
import AvailabilityBadge from './AvailabilityBadge';
import OrganizerCard from './OrganizerCard';

export interface EventCardProps {
  event: import('../types/shared').Event;
  onClick?: (event: import('../types/shared').Event) => void;
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

const getAvailabilityClass = (ticketsAvailable: number | null, ticketsTotal: number): string => {
  if (ticketsTotal === 0 || (ticketsAvailable != null && ticketsAvailable <= 0)) return 'sold-out';
  const percentAvailable = ticketsAvailable != null ? (ticketsAvailable / ticketsTotal) * 100 : 100;
  if (percentAvailable > 20) return 'available';
  if (percentAvailable >= 5) return 'low';
  return 'sold-out';
};

const getAvailabilityText = (ticketsAvailable: number | null, ticketsTotal: number): string => {
  if (ticketsTotal === 0 || (ticketsAvailable != null && ticketsAvailable <= 0)) return 'Sold Out';
  if (ticketsAvailable != null) return `${ticketsAvailable} tickets left`;
  return 'Tickets available';
};

const EventCard = ({
  event,
  onClick,
  loading = false,
}: EventCardProps) => {
  const [hover, setHover] = React.useState(false);
  const [priceHover, setPriceHover] = React.useState(false);
  const eventAny = event as unknown as { [key: string]: any };
  const imageUrl =
    eventAny.banner_image_url || eventAny.image_url || eventAny.banner_image || fallbackImage;
  const venue = eventAny.venue_name || eventAny.venue?.name || eventAny.venue || 'Venue TBA';
  const organizer = eventAny.organizer;
  const organizerName =
    organizer?.displayName || organizer?.name || 'Unknown Organizer';
  const startDateTime = eventAny.start_datetime || eventAny.start_date;
  const price = eventAny.ticket_price != null ? Number(eventAny.ticket_price) : null;
  const ticketsRemaining =
    eventAny.tickets_remaining ?? eventAny.ticketsRemaining;
  const totalCapacity =
    eventAny.total_capacity || (eventAny.capacity ? eventAny.capacity : 5000);
  const status = eventAny.status || 'published';

  const availabilityClass = getAvailabilityClass(ticketsRemaining, totalCapacity);
  const availabilityText = getAvailabilityText(ticketsRemaining, totalCapacity);

  const progressPercent = ticketsRemaining != null
    ? Math.max(0, Math.min(100, ((totalCapacity - ticketsRemaining) / totalCapacity) * 100))
    : 100;

  const handleBuyClick = () => {
    showToast(
      'Ticket Purchased',
      `You successfully purchased a ticket for ${event.title || event.name}`,
      'success'
    );
    onClick?.(event);
  };

  return (
    <article
      className=`event-card cursor-pointer hover:shadow-md transition-shadow duration-150 ${hover ? 'ring-2 ring-indigo-500' : ''}`
      onClick={() => onClick?.(event)}
      onMouseEnter={() => setHover(true)}
      onMouseLeave={() => setHover(false)}
      role="article"
      aria-label={event.title || event.name || 'Event card'}
    >
      <div
        className="event-image-container overflow-hidden"
        onMouseEnter={() => setPriceHover(true)}
        onMouseLeave={() => setPriceHover(false)}
      >
        <img
          src={imageUrl}
          alt={event.title || event.name || 'Event preview'}
          className="event-image"
          loading="lazy"
          onError={() => {
            // fallback handled by CSS background
          }}
          style={{ objectFit: 'cover', width: '100%', height: '100%' }}
        />
        {event.category && <span className="event-badge category">{event.category}</span>}
        <span className={`event-status ${status === 'draft' ? 'event-status--draft' : status === 'archived' ? 'event-status--archived' : 'event-status--published'}`}>
          {status === 'draft' ? 'Draft' : status === 'archived' ? 'Archived' : 'Published'}
        </span>
      </div>
      <div className="event-details">
        <h3
          className="event-title"
          style={{
            display: '-webkit-box',
            '-webkitLineClamp': '2',
            '-webkitBoxOrient': 'max-line',
            overflow: 'hidden',
            marginBottom: '8px',
            WebkitFontSmoothing: 'antialiased',
          }}
        >
          {event.title || event.name || 'Untitled Event'}
        </h3>
        <div className="event-meta">
          <div className="meta-row">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
              <line x1="16" y1="2" x2="16" y2="6" />
              <line x1="8" y1="2" x2="8" y2="6" />
              <line x1="3" y1="10" x2="21" y2="10" />
            </svg>
            <span>{formatDate(startDateTime)} {formatTime(startDateTime)}</span>
          </div>
          <div className="meta-row">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z" />
              <circle cx="12" cy="10" r="3" />
            </svg>
            <span>{venue}{eventAny.city ? ` · ${eventAny.city}` : ''}</span>
          </div>
        </div>
        <div className="event-footer">
          <div className="event-price">
            <span className="price-label">From</span>
            <span
              className={`price-value ${priceHover ? 'price-value--hover' : ''}`}
            >
              {price != null ? `₦${price.toLocaleString()}` : 'Free'}
            </span>
          </div>
          <span className={`availability ${availabilityClass}`}>
            {availabilityText}
          </span>
          {eventAny.ticket_tiers &&
          Array.isArray(eventAny.ticket_tiers) &&
          eventAny.ticket_tiers.some(
            (t: any) => t.is_visible && !t.is_sold_out,
          ) &&
          <span className="event-badge trending">Trending</span>}
        </div>
      </div>
      {loading ? (
        <div className="skeleton-card" />
      ) : onClick && (
        <button
          onClick={handleBuyClick}
          className="btn-view-event w-full py-2 text-sm font-medium text-indigo-600 rounded-md hover:bg-indigo-50 transition-colors"
          aria-label="View event details"
        >
          View Event
        </button>
      )}
    </article>
  );
};

export default React.memo(EventCard);