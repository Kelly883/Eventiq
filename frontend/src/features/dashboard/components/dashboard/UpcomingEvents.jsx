import React from 'react';
import Icon from './Icon';

const UpcomingEvents = ({ events = [], loading = false, onBrowseEvents }) => {
  if (loading) {
    return (
      <section className="dashboard-section" aria-label="Upcoming events">
        <h2 className="section-heading">Upcoming Events</h2>
        <div className="events-list">
          {[1, 2, 3].map((i) => (
            <div key={i} className="event-card-skeleton" aria-hidden="true" />
          ))}
        </div>
      </section>
    );
  }

  if (!events || events.length === 0) {
    return (
      <section className="dashboard-section" aria-label="Upcoming events">
        <h2 className="section-heading">Upcoming Events</h2>
        <div className="empty-state">
          <Icon name="calendar" size={48} className="empty-state-icon" />
          <h3 className="empty-state-title">No upcoming events</h3>
          <p className="empty-state-text">
            Discover events that match your interests and book your spot.
          </p>
          <button type="button" className="btn-primary" onClick={onBrowseEvents}>
            Browse Events
          </button>
        </div>
      </section>
    );
  }

  return (
    <section className="dashboard-section" aria-label="Upcoming events">
      <h2 className="section-heading">Upcoming Events</h2>
      <div className="events-list">
        {events.map((event) => (
          <article key={event.id} className="event-card">
            <div className="event-card-image">
              {event.image ? (
                <img src={event.image} alt={event.title} className="event-card-img" />
              ) : (
                <div className="event-card-image-placeholder" aria-hidden="true" />
              )}
            </div>
            <div className="event-card-content">
              <h3 className="event-card-title">{event.title}</h3>
              <div className="event-card-meta">
                <Icon name="calendar" size={14} />
                <time dateTime={event.start_datetime}>
                  {new Date(event.start_datetime).toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                  })}
                </time>
              </div>
              <p className="event-card-location">{event.location || 'Location TBA'}</p>
            </div>
            <button type="button" className="btn-secondary event-card-cta">
              View Details
            </button>
          </article>
        ))}
      </div>
    </section>
  );
};

export default UpcomingEvents;
