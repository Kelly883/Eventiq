import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';

const Header = () => {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);

  // Two visual states: transparent white text over the hero, then a solid
  // white surface once scrolled. A 6px threshold keeps the swap from firing
  // on micro-scrolls. The .is-scrolled class drives the color inversion in CSS.
  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 6);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  const handleNavClick = () => {
    setMobileMenuOpen(false);
  };

  return (
    <header className={`header${scrolled ? ' is-scrolled' : ''}`}>
      <div className="header-container">
        <Link to="/" className="logo" aria-label="Eventiq home">
          <span className="logo-icon" aria-hidden="true">◆</span>
          <span className="logo-text">EVENTIQ</span>
        </Link>

        <nav className="nav-desktop" aria-label="Primary">
          <Link to="/events" className="nav-link">Discover</Link>
          <Link to="/events" className="nav-link">Categories</Link>
          <Link to="/organizer/events/create" className="nav-link">For Organizers</Link>
        </nav>

        <div className="nav-actions">
          <Link to="/login" className="btn-text">Sign in</Link>
          <Link to="/register" className="btn-primary">Create account</Link>
        </div>

        <button
          className="mobile-menu-btn"
          type="button"
          onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
          aria-label={mobileMenuOpen ? 'Close navigation' : 'Open navigation'}
          aria-expanded={mobileMenuOpen}
          aria-controls="mobile-menu"
        >
          <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" strokeWidth="2" aria-hidden="true">
            {mobileMenuOpen ? (
              <path d="M18 6L6 18M6 6l12 12" />
            ) : (
              <>
                <path d="M3 12h18" />
                <path d="M3 6h18" />
                <path d="M3 18h18" />
              </>
            )}
          </svg>
        </button>
      </div>

      {mobileMenuOpen && (
        <nav className="mobile-menu" id="mobile-menu" aria-label="Mobile">
          <Link to="/events" className="mobile-nav-link" onClick={handleNavClick}>Discover</Link>
          <Link to="/events" className="mobile-nav-link" onClick={handleNavClick}>Categories</Link>
          <Link to="/events/calendar" className="mobile-nav-link" onClick={handleNavClick}>Calendar</Link>
          <Link to="/organizer/events/create" className="mobile-nav-link" onClick={handleNavClick}>For Organizers</Link>
          <Link to="/trust" className="mobile-nav-link" onClick={handleNavClick}>Trust &amp; Safety</Link>
          <div className="mobile-nav-actions">
            <Link to="/login" className="btn-text" onClick={handleNavClick}>Sign in</Link>
            <Link to="/register" className="btn-primary" onClick={handleNavClick}>Create account</Link>
          </div>
        </nav>
      )}
    </header>
  );
};

export default Header;