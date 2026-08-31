import React from 'react';
import { Link } from 'react-router-dom';
import { useUpcomingEvents } from '../hooks/useHomepageData';

const DATE_FORMATTER = new Intl.DateTimeFormat('en-US', {
  month: 'short',
  day: 'numeric',
});
const TIME_FORMATTER = new Intl.DateTimeFormat('en-US', {
  hour: 'numeric',
  minute: '2-digit',
  hour12: true,
});
const MONTH_FORMATTER = new Intl.DateTimeFormat('en-US', { month: 'short' });
const DAY_FORMATTER = new Intl.DateTimeFormat('en-US', { day: '2-digit' });

const getAvailabilityClass = (tickets) => {
  if (tickets === 0) return 'sold-out';
  if (tickets <= 50) return 'low';
  return 'available';
};

const UpcomingEventsSection = () => {
  const { data: upcomingEvents, isLoading, isError } = useUpcomingEvents();

  if (isLoading) {
    return (
      <section className="upcoming-section">
        <div className="section-container">
          <div className="section-header">
            <div>
              <p className="section-eyebrow">What's coming up</p>
              <h2 className="section-title">Upcoming events near you</h2>
              <p className="section-subtitle">Fresh tickets, new dates, live venues.</p>
            </div>
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
            <p className="section-eyebrow">What's coming up</p>
            <h2 className="section-title">Upcoming events near you</h2>
            <p className="section-subtitle">Fresh tickets, new dates, live venues.</p>
          </div>
        </div>
        <div className="events-list">
          {upcomingEvents.map((event, index) => {
            const ticketsRemaining = event.tickets_remaining ?? event.ticketsRemaining ?? null;
            const start = event.start_datetime || event.start_date || event.date;
            const startDate = start ? new Date(start) : null;
            const isValidDate = startDate && !Number.isNaN(startDate.getTime());
            const venue = event.venue_name || event.venue?.name || event.venue || 'Venue TBA';
            const city = event.location || event.city || '';
            const price = event.ticket_price != null ? Number(event.ticket_price) : null;

            return (
              <article key={event.id} className="event-list-item">
                <Link to={`/events/${event.id}`} className="event-list-link">
                  <time className="event-list-date" dateTime={isValidDate ? start : undefined}>
                    {isValidDate ? (
                      <>
                        <span className="event-list-date-month">{MONTH_FORMATTER.format(startDate)}</span>
                        <span className="event-list-date-day">{DAY_FORMATTER.format(startDate)}</span>
                      </>
                    ) : (
                      <span className="event-list-date-day tba">TBA</span>
                    )}
                  </time>
                  <div className="event-list-details">
                    <h3 className="event-list-title">{event.name || event.title || 'Untitled Event'}</h3>
                    <p className="event-list-venue">{venue}{city ? ` · ${city}` : ''}</p>
                  </div>
                  <div className="event-list-meta">
                    <span className="event-list-time">
                      {isValidDate ? TIME_FORMATTER.format(startDate) : 'Time TBA'}
                    </span>
                    <span className="event-list-price">
                      {price != null ? `From ₦${price.toLocaleString()}` : 'Free'}
                    </span>
                  </div>
                  <span className={`availability ${getAvailabilityClass(ticketsRemaining)}`}>
                    {ticketsRemaining === 0 ? 'Sold out' : ticketsRemaining != null ? `${ticketsRemaining} tickets left` : 'Tickets available'}
                  </span>
                  <span className="btn-grab-seats">
                    Grab seats
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" aria-hidden="true">
                      <path d="M5 12h14M13 6l6 6-6 6" />
                    </svg>
                  </span>
                </Link>
              </article>
            );
          })}
        </div>
      </div>
    </section>
  );
};

export default UpcomingEventsSection;