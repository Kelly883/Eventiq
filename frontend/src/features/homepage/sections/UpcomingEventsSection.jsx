import React from 'react';
import { Link } from 'react-router-dom';
import { useUpcomingEvents } from '../hooks/useHomepageData';

const UpcomingEventsSection = () => {
  const { data: upcomingEvents, isLoading, isError } = useUpcomingEvents();

  const getAvailabilityClass = (tickets) => {
    if (tickets === 0) return 'sold-out';
    if (tickets <= 50) return 'low';
    return 'available';
  };

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

  if (isLoading) {
    return (
      <section className="upcoming-section">
        <div className="section-container">
          <div className="section-header">
            <h2 className="section-title">Upcoming Events Near You</h2>
          </div>
          <div className="events-list">
            {[1, 2, 3, 4].map((i) => (
              <div key={i} className="event-list-item skeleton">
                <div className="skeleton-thumb" />
                <div className="skeleton-content">
                  <div className="skeleton-title" />
                  <div className="skeleton-text" />
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>
    );
  }

  if (isError || !upcomingEvents?.length) {
    return null;
  }

  return (
    <section className="upcoming-section">
      <div className="section-container">
        <div className="section-header">
          <div>
            <h2 className="section-title">Upcoming Events Near You</h2>
            <div className="location-indicator">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z" />
                <circle cx="12" cy="10" r="3" />
              </svg>
              <span>Discover events near you</span>
            </div>
          </div>
        </div>
        <div className="events-list">
          {upcomingEvents.map((event) => {
            const imageUrl = event.banner_image_url || event.image_url || event.image || 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=200&h=200&fit=crop';
            const ticketsRemaining = event.tickets_remaining ?? event.ticketsRemaining ?? 0;

            return (
              <article key={event.id} className="event-list-item">
                <div className="event-list-image" style={{ backgroundImage: `url(${imageUrl})` }} />
                <div className="event-list-details">
                  <h3 className="event-list-title">{event.name || event.title || 'Untitled Event'}</h3>
                  <p className="event-list-organizer">{event.organizer?.name || event.organizer || 'Organizer'}</p>
                  <p className="event-list-venue">
                    {event.venue_name || event.venue?.name || event.venue || 'TBA'} · {event.location || event.city || ''}
                  </p>
                </div>
                <div className="event-list-meta">
                  <span className="event-list-date">{formatDate(event.start_date || event.date)}</span>
                  <span className="event-list-time">{formatTime(event.start_date || event.date)}</span>
                </div>
                <div className="event-list-actions">
                  <span className={`availability ${getAvailabilityClass(ticketsRemaining)}`}>
                    {ticketsRemaining > 0 ? `${ticketsRemaining} tickets left` : 'Sold out'}
                  </span>
                  <span className="event-list-price">
                    {event.ticket_price ? `From ₦${Number(event.ticket_price).toLocaleString()}` : 'Free'}
                  </span>
                  <Link to={`/events/${event.id}`} className="btn-grab-seats">
                    Grab Seats
                  </Link>
                </div>
              </article>
            );
          })}
        </div>
      </div>
    </section>
  );
};

export default UpcomingEventsSection;
