import React from 'react';
import { Link } from 'react-router-dom';

const OrganizerCTASection = () => {
  return (
    <section className="organizer-section">
      <div className="section-container">
        <div className="organizer-panel">
          <div className="organizer-content">
            <p className="organizer-eyebrow">For organizers</p>
            <h2 className="organizer-headline">Have an event? Sell tickets with EventIQ.</h2>
            <p className="organizer-subheadline">
              Create your event, sell tickets, manage attendees and track performance from one place.
            </p>
            <div className="organizer-actions">
              <Link to="/organizer/events/create" className="btn-organizer-cta">
                Create an event
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" aria-hidden="true">
                  <path d="M5 12h14M13 6l6 6-6 6" />
                </svg>
              </Link>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

export default OrganizerCTASection;