import React from 'react';
import { Link } from 'react-router-dom';

const EventCard = ({ event }) => {
  const getAvailabilityStatus = (tickets) => {
    if (tickets === 0) return { label: 'Sold out', className: 'sold-out' };
    if (tickets <= 30) return { label: `${tickets} tickets remaining`, className: 'low' };
    return { label: `${tickets} tickets remaining`, className: 'available' };
  };

  const availability = getAvailabilityStatus(event.ticketsRemaining);

  return (
    <article className="event-card">
      <Link to={`/events/${event.id}`} className="event-card-link">
        <div className="event-image-container">
          <div className="event-image" style={{ backgroundImage: `url(${event.image})` }} />
          {event.badge && (
            <span className={`event-badge ${event.badge.toLowerCase()}`}>
              {event.badge}
            </span>
          )}
        </div>
        <div className="event-details">
          <h3 className="event-title">{event.title}</h3>
          <div className="event-organizer">
            <div className="organizer-avatar" style={{ backgroundImage: `url(${event.organizerAvatar})` }} />
            <span className="organizer-name">{event.organizer}</span>
          </div>
          <div className="event-meta">
            <div className="meta-row">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                <line x1="16" y1="2" x2="16" y2="6" />
                <line x1="8" y1="2" x2="8" y2="6" />
                <line x1="3" y1="10" x2="21" y2="10" />
              </svg>
              <span>{event.date} · {event.time}</span>
            </div>
            <div className="meta-row">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z" />
                <circle cx="12" cy="10" r="3" />
              </svg>
              <span>{event.venue} · {event.location}</span>
            </div>
          </div>
          <div className="event-footer">
            <div className="event-price">
              <span className="price-label">From</span>
              <span className="price-value">{event.price}</span>
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
  const trendingEvents = [
    {
      id: 1,
      title: 'Afrobeats Summer Festival 2026',
      organizer: 'Live Nation Africa',
      organizerAvatar: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=40&h=40&fit=crop',
      image: 'https://images.unsplash.com/photo-1459749411175-04bf5292ceea?w=600&h=400&fit=crop',
      date: 'Sat, 15 Nov',
      time: '4:00 PM',
      venue: 'Eko Convention Center',
      location: 'Lagos, Nigeria',
      price: '₦15,000',
      ticketsRemaining: 132,
      badge: 'Trending',
    },
    {
      id: 2,
      title: 'Tech Summit Africa',
      organizer: 'TechCabal',
      organizerAvatar: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=40&h=40&fit=crop',
      image: 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=600&h=400&fit=crop',
      date: 'Mon, 17 Nov',
      time: '9:00 AM',
      venue: 'Landmark Centre',
      location: 'Lagos, Nigeria',
      price: '₦25,000',
      ticketsRemaining: 28,
      badge: 'Popular',
    },
    {
      id: 3,
      title: 'Comedy Night: Fresh Laughs',
      organizer: 'Basketmouth',
      organizerAvatar: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=40&h=40&fit=crop',
      image: 'https://images.unsplash.com/photo-1585699324551-f6c309eedeca?w=600&h=400&fit=crop',
      date: 'Fri, 21 Nov',
      time: '7:00 PM',
      venue: 'Eko Hotels',
      location: 'Lagos, Nigeria',
      price: '₦10,000',
      ticketsRemaining: 0,
      badge: 'Hot',
    },
  ];

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
