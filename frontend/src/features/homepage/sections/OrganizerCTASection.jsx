import React from 'react';
import { Link } from 'react-router-dom';

const OrganizerCTASection = () => {
  return (
    <section className="organizer-section">
      <div className="section-container">
        <div className="organizer-panel">
          <div className="organizer-content">
            <h2 className="organizer-headline">Have an event? Sell tickets with EventIQ.</h2>
            <p className="organizer-subheadline">
              Create your event, sell tickets, manage attendees and track performance from one place.
            </p>
            <div className="organizer-actions">
              <Link to="/organizer/events/create" className="btn-primary">
                Create an event
              </Link>
              <Link to="/organizer/events" className="btn-secondary">
                Organizer dashboard
              </Link>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

export default OrganizerCTASection;
