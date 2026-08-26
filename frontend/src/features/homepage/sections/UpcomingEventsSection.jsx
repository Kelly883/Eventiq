import React from 'react';
import { Link } from 'react-router-dom';

const UpcomingEventsSection = () => {
  const upcomingEvents = [
    {
      id: 4,
      title: 'Jazz Night: Smooth Sessions',
      organizer: 'Lagos Jazz Club',
      image: 'https://images.unsplash.com/photo-1415201364774-f6f0bb35f28f?w=200&h=200&fit=crop',
      date: 'Fri, 30 Aug',
      time: '10:00 PM',
      venue: 'The Jazz Hole',
      location: 'Lagos',
      price: 'From ₦5,000',
      ticketsRemaining: 64,
    },
    {
      id: 5,
      title: 'Startup Pitch Night',
      organizer: 'Founders Hub',
      image: 'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?w=200&h=200&fit=crop',
      date: 'Sat, 31 Aug',
      time: '6:00 PM',
      venue: 'Co-Creation Hub',
      location: 'Lagos',
      price: 'Free',
      ticketsRemaining: 120,
    },
    {
      id: 6,
      title: 'Art Gallery Opening: New Voices',
      organizer: 'Nike Art Gallery',
      image: 'https://images.unsplash.com/photo-1531243269054-5ebf6f34081e?w=200&h=200&fit=crop',
      date: 'Sun, 1 Sep',
      time: '2:00 PM',
      venue: 'Nike Art Gallery',
      location: 'Lekki',
      price: 'From ₦2,500',
      ticketsRemaining: 45,
    },
    {
      id: 7,
      title: 'Food & Wine Festival',
      organizer: 'Lagos Food Fair',
      image: 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=200&h=200&fit=crop',
      date: 'Sat, 7 Sep',
      time: '11:00 AM',
      venue: 'Muriel Park',
      location: 'Lagos',
      price: 'From ₦8,000',
      ticketsRemaining: 200,
    },
  ];

  const getAvailabilityClass = (tickets) => {
    if (tickets === 0) return 'sold-out';
    if (tickets <= 50) return 'low';
    return 'available';
  };

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
              <span>Lagos, Nigeria</span>
              <button className="btn-location">Use my location</button>
            </div>
          </div>
        </div>
        <div className="events-list">
          {upcomingEvents.map((event) => (
            <article key={event.id} className="event-list-item">
              <div className="event-list-image" style={{ backgroundImage: `url(${event.image})` }} />
              <div className="event-list-details">
                <h3 className="event-list-title">{event.title}</h3>
                <p className="event-list-organizer">{event.organizer}</p>
                <p className="event-list-venue">{event.venue} · {event.location}</p>
              </div>
              <div className="event-list-meta">
                <span className="event-list-date">{event.date}</span>
                <span className="event-list-time">{event.time}</span>
              </div>
              <div className="event-list-actions">
                <span className={`availability ${getAvailabilityClass(event.ticketsRemaining)}`}>
                  {event.ticketsRemaining > 0 ? `${event.ticketsRemaining} tickets left` : 'Sold out'}
                </span>
                <span className="event-list-price">{event.price}</span>
                <Link to={`/events/${event.id}`} className="btn-grab-seats">
                  Grab Seats
                </Link>
              </div>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
};

export default UpcomingEventsSection;
