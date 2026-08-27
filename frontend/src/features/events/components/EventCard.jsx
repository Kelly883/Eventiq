import React from 'react';
import { Link } from 'react-router-dom';

// Renders one event from the anonymous EventPublicResource shape
// (backend/app/Http/Resources/EventPublicResource.php): title, start_date,
// venue / location, category, image_url, ticket_price, tickets_remaining.
// Also tolerates a couple of legacy/alternate field names via fallback
// chains, so this card stays safe to reuse if that contract shifts again.
const fallbackImage =
  'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=600&h=400&fit=crop';

function formatDate(dateString) {
  if (!dateString) return 'Date TBA';
  const date = new Date(dateString);
  if (Number.isNaN(date.getTime())) return 'Date TBA';
  return date.toLocaleDateString('en-US', {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
  });
}

function formatTime(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  if (Number.isNaN(date.getTime())) return '';
  return date.toLocaleTimeString('en-US', {
    hour: 'numeric',
    minute: '2-digit',
    hour12: true,
  });
}

const EventCard = ({ event }) => {
  const imageUrl = event.banner_image_url || event.image_url || event.image || fallbackImage;
  const venue = event.venue_name || event.venue?.name || event.venue || 'Venue TBA';
  const location = event.city || event.location || '';
  const price = event.ticket_price != null ? Number(event.ticket_price) : null;
  const ticketsRemaining = event.tickets_remaining ?? event.ticketsRemaining;

  return (
    <article className="event-card">
      <Link to={`/events/${event.id}`} className="event-card-link">
        <div className="event-image-container">
          <div className="event-image" style={{ backgroundImage: `url(${imageUrl})` }} />
          {event.category && <span className="event-badge category">{event.category}</span>}
        </div>
        <div className="event-details">
          <h3 className="event-title">{event.title || event.name || 'Untitled Event'}</h3>
          <div className="event-meta">
            <div className="meta-row">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                <line x1="16" y1="2" x2="16" y2="6" />
                <line x1="8" y1="2" x2="8" y2="6" />
                <line x1="3" y1="10" x2="21" y2="10" />
              </svg>
              <span>{formatDate(event.start_datetime || event.start_date)} {formatTime(event.start_datetime || event.start_date)}</span>
            </div>
            <div className="meta-row">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z" />
                <circle cx="12" cy="10" r="3" />
              </svg>
              <span>{venue}{location ? ` · ${location}` : ''}</span>
            </div>
          </div>
          <div className="event-footer">
            <div className="event-price">
              <span className="price-label">From</span>
              <span className="price-value">
                {price != null ? `₦${price.toLocaleString()}` : 'Free'}
              </span>
            </div>
            <span className={`availability ${ticketsRemaining === 0 ? 'sold-out' : ticketsRemaining != null && ticketsRemaining <= 30 ? 'low' : 'available'}`}>
              {ticketsRemaining === 0 ? 'Sold out' : ticketsRemaining != null ? `${ticketsRemaining} tickets remaining` : 'Tickets available'}
            </span>
          </div>
        </div>
      </Link>
      <Link to={`/events/${event.id}`} className="btn-view-event">
        View Event
      </Link>
    </article>
  );
};

export default EventCard;