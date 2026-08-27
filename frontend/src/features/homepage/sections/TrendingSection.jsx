import React from 'react';
import { Link } from 'react-router-dom';
import { useTrendingEvents } from '../hooks/useHomepageData';

const EventCard = ({ event }) => {
  const getAvailabilityStatus = (tickets) => {
    if (tickets == null) return { label: 'Tickets available', className: 'available' };
    if (tickets === 0) return { label: 'Sold out', className: 'sold-out' };
    if (tickets <= 30) return { label: `${tickets} tickets remaining`, className: 'low' };
    return { label: `${tickets} tickets remaining`, className: 'available' };
  };

  // Use ticketsRemaining == null to mean "unlimited", so a missing value never
  // renders as the literal string "undefined tickets remaining".
  const ticketsRemaining = event.tickets_remaining ?? event.ticketsRemaining ?? null;
  const availability = getAvailabilityStatus(ticketsRemaining);
  const imageUrl = event.image_url || event.image || 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=600&h=400&fit=crop';
  const organizerName = event.organizer?.name || event.organizer || 'Organizer';
  const organizerAvatar = event.organizer?.avatar_url || event.organizerAvatar || 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=40&h=40&fit=crop';

  const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
  };

  const formatTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
  };

  const getBadge = () => {
    if (ticketsRemaining === 0) return 'Sold Out';
    if (event.is_featured || event.isFeatured) return 'Featured';
    return null;
  };

  const badge = getBadge();

  return (
    <article className="event-card">
      <Link to={`/events/${event.id}`} className="event-card-link">
        <div className="event-image-container">
          <div className="event-image" style={{ backgroundImage: `url(${imageUrl})` }} />
          {badge && (
            <span className={`event-badge ${badge.toLowerCase().replace(' ', '-')}`}>
              {badge}
            </span>
          )}
        </div>
        <div className="event-details">
          <h3 className="event-title">{event.name || event.title || 'Untitled Event'}</h3>
          <div className="event-organizer">
            <div className="organizer-avatar" style={{ backgroundImage: `url(${organizerAvatar})` }} />
            <span className="organizer-name">{organizerName}</span>
          </div>
          <div className="event-meta">
            <div className="meta-row">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                <line x1="16" y1="2" x2="16" y2="6" />
                <line x1="8" y1="2" x2="8" y2="6" />
                <line x1="3" y1="10" x2="21" y2="10" />
              </svg>
              <span>{formatDate(event.start_datetime || event.start_date || event.date)} · {formatTime(event.start_datetime || event.start_date || event.date)}</span>
            </div>
            <div className="meta-row">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z" />
                <circle cx="12" cy="10" r="3" />
              </svg>
              <span>{event.venue_name || event.venue?.name || event.venue || 'TBA'} · {event.location || event.city || ''}{event.venue_address || ''}</span>
            </div>
          </div>
          <div className="event-footer">
            <div className="event-price">
              <span className="price-label">From</span>
              <span className="price-value">
                {event.ticket_price ? `₦${Number(event.ticket_price).toLocaleString()}` : 'Free'}
              </span>
            </div>
            <span className={`availability ${availability.className}`}>
              {availability.label}
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

const TrendingSection = () => {
  const { data: trendingEvents, isLoading, isError } = useTrendingEvents();

  if (isLoading) {
    return (
      <section className="trending-section">
        <div className="section-container">
          <div className="section-header">
            <h2 className="section-title">Trending Now</h2>
            <p className="section-subtitle">Discover the events people are talking about.</p>
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
          <h2 className="section-title">Trending Now</h2>
          <p className="section-subtitle">Discover the events people are talking about.</p>
        </div>
        <div className="events-grid">
          {trendingEvents.map((event) => (
            <EventCard key={event.id} event={event} />
          ))}
        </div>
      </div>
    </section>
  );
};

export default TrendingSection;
