import React from 'react';
import { Link } from 'react-router-dom';

const HeroSection = () => {
  return (
    <section className="hero">
      <div className="hero-background">
        <div className="hero-overlay" />
      </div>
      <div className="hero-content">
        <p className="hero-kicker"><span className="hero-kicker-dot" />Your next great memory starts here</p>
          <h1 className="hero-headline">
            Make plans worth remembering.
          </h1>
          <p className="hero-subheadline">
            Discover concerts, festivals, comedy, sports, conferences and experiences happening around you.
          </p>
          <div className="hero-actions">
            <Link to="/events" className="btn-hero-primary">
              Explore events
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" aria-hidden="true">
                <path d="M5 12h14M13 6l6 6-6 6" />
              </svg>
            </Link>
            <Link to="/organizer/events/create" className="btn-hero-secondary">
              Host an event
            </Link>
          </div>
          <div className="hero-proof" aria-label="Eventiq benefits">
            <span><strong>10k+</strong> experiences to discover</span>
            <span className="hero-proof-divider" />
            <span>Secure checkout</span>
          </div>
      </div>
    </section>
  );
};

export default HeroSection;
