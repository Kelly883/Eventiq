import React from 'react';
import { Link } from 'react-router-dom';

const OrganizerCTASection = () => {
  return (
    <section className="organizer-section">
      <div className="organizer-background">
        <div className="organizer-overlay" />
      </div>
      <div className="organizer-content">
        <h2 className="organizer-headline">Your event deserves a full house.</h2>
        <p className="organizer-subheadline">
          Create your event, sell tickets, manage attendees and track your event from one place.
        </p>
        <div className="organizer-actions">
          <Link to="/organizer/events/create" className="btn-primary">
            Create an event
          </Link>
          <Link to="/organizer/events" className="btn-secondary">
            Explore organizer tools
          </Link>
        </div>
      </div>
    </section>
  );
};

export default OrganizerCTASection;
