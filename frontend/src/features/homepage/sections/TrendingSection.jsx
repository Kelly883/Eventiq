import React from 'react';
import { Link } from 'react-router-dom';
import { useTrendingEvents } from '../hooks/useHomepageData';

// Distinct, subject-appropriate fallback photography. Real event imagery from
// the API always wins; this set only appears when an event has no image, and
// gives each card its own visual so the grid never reads as repeated.
const FALLBACK_IMAGES = [
  'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=800&h=500&fit=crop',
  'https://images.unsplash.com/photo-1533174072545-7a4b6ad7a6c3?w=800&h=500&fit=crop',
  'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=800&h=500&fit=crop',
  'https://images.unsplash.com/photo-1503095396549-807759245b35?w=800&h=500&fit=crop',
];

const EVENT_DATE_FORMATTER = new Intl.DateTimeFormat('en-US', {
  weekday: 'short',
  month: 'short',
  day: 'numeric',
});
const EVENT_TIME_FORMATTER = new Intl.DateTimeFormat('en-US', {
  hour: 'numeric',
  minute: '2-digit',
  hour12: true,
});

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  if (Number.isNaN(date.getTime())) return '';
  return EVENT_DATE_FORMATTER.format(date);
};

const formatTime = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  if (Number.isNaN(date.getTime())) return '';
  return EVENT_TIME_FORMATTER.format(date);
};

const EventCard = ({ event, index }) => {
  const ticketsRemaining = event.tickets_remaining ?? event.ticketsRemaining ?? null;

  const imageUrl =
    event.banner_image_url ||
    event.image_url ||
    event.image ||
    FALLBACK_IMAGES[index % FALLBACK_IMAGES.length];

  const venue = event.venue_name || event.venue?.name || event.venue || 'Venue TBA';
  const city = event.location || event.city || '';
  const start = event.start_datetime || event.start_date || event.date;
  const price = event.ticket_price != null ? Number(event.ticket_price) : null;
  const dateLabel = formatDate(start);
  const timeLabel = formatTime(start);

  const badge = ticketsRemaining === 0
    ? { text: 'Sold Out', className: 'sold-out' }
    : event.is_featured || event.isFeatured
      ? { text: 'Featured', className: 'featured' }
      : null;

  const availability = ticketsRemaining == null
    ? { label: 'Tickets available', className: 'available' }
    : ticketsRemaining === 0
      ? { label: 'Sold out', className: 'sold-out' }
      : ticketsRemaining <= 30
        ? { label: `${ticketsRemaining} tickets left`, className: 'low' }
        : { label: `${ticketsRemaining} tickets left`, className: 'available' };

  return (
    <article className="event-card">
      <Link to={`/events/${event.id}`} className="event-card-link">
        <div className="event-image-container">
          <div className="event-image" aria-hidden="true" style={{ backgroundImage: `url(${imageUrl})` }} />
          {badge && (
            <span className={`event-badge ${badge.className}`}>{badge.text}</span>
          )}
        </div>
        <div className="event-details">
          <h3 className="event-title">{event.name || event.title || 'Untitled Event'}</h3>
          <div className="event-meta">
            <div className="meta-row meta-row-date">
              {(dateLabel || timeLabel) &&
                <span>{[dateLabel, timeLabel].filter(Boolean).join(' · ')}</span>}
              {!dateLabel && !timeLabel && <span>Date TBA</span>}
            </div>
            <div className="meta-row">
              <span>{venue}{city ? ` · ${city}` : ''}</span>
            </div>
          </div>
          <div className="event-footer">
            <div className="event-price">
              <span className="price-label">From</span>
              <span className="price-value">
                {price != null ? `₦${price.toLocaleString()}` : 'Free'}
              </span>
            </div>
            <span className={`availability ${availability.className}`}>{availability.label}</span>
          </div>
          <span className="event-card-cta">
            View event
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" aria-hidden="true">
              <path d="M5 12h14M13 6l6 6-6 6" />
            </svg>
          </span>
        </div>
      </Link>
    </article>
  );
};

const TrendingSection = () => {
  const { data: trendingEvents, isLoading, isError } = useTrendingEvents();

  if (isLoading) {
    return (
      <section className="trending-section">
        <div className="section-container">
          <div className="section-header">
            <div>
              <p className="section-eyebrow">Live this week</p>
              <h2 className="section-title">Trending now</h2>
              <p className="section-subtitle">Discover the events people are talking about.</p>
            </div>
          </div>
          <div className="events-grid">
            {[1, 2, 3].map((i) => (
              <div key={i} className="event-card skeleton">
                <div className="skeleton-image" />
                <div className="skeleton-content">
                  <div className="skeleton-title" />
                  <div className="skeleton-text" />
                  <div className="skeleton-text short" />
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>
    );
  }

  if (isError || !trendingEvents?.length) {
    return null;
  }

  return (
    <section className="trending-section">
      <div className="section-container">
        <div className="section-header">
          <div>
            <p className="section-eyebrow">Live this week</p>
            <h2 className="section-title">Trending now</h2>
            <p className="section-subtitle">Discover the events people are talking about.</p>
          </div>
          <Link to="/events" className="section-link">
            View all events
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" aria-hidden="true">
              <path d="M5 12h14M13 6l6 6-6 6" />
            </svg>
          </Link>
        </div>
        <div className="events-grid">
          {trendingEvents.map((event, index) => (
            <EventCard key={event.id} event={event} index={index} />
          ))}
        </div>
      </div>
    </section>
  );
};

export default TrendingSection;