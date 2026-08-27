import React, { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';

const Header = () => {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const navigate = useNavigate();

  const handleNavClick = () => {
    setMobileMenuOpen(false);
  };

  return (
    <header className="header">
      <div className="header-container">
        <Link to="/" className="logo">
          <span className="logo-icon">◆</span>
          <span className="logo-text">EVENTIQ</span>
        </Link>

        <nav className="nav-desktop">
          <Link to="/events" className="nav-link">Discover</Link>
          <Link to="/events?category=concerts" className="nav-link">Categories</Link>
          <Link to="/organizer/events/create" className="nav-link">For Organizers</Link>
          <Link to="/trust" className="nav-link">Trust & Safety</Link>
        </nav>

        <div className="nav-actions">
          <button className="btn-icon" aria-label="Search">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
              <circle cx="11" cy="11" r="8" />
              <path d="m21 21-4.35-4.35" />
            </svg>
          </button>
          <Link to="/login" className="btn-text">Sign in</Link>
          <Link to="/register" className="btn-primary">Create account</Link>
        </div>

        <button
          className="mobile-menu-btn"
          onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
          aria-label="Menu"
          aria-expanded={mobileMenuOpen}
        >
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
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
        <nav className="mobile-menu">
          <Link to="/events" className="mobile-nav-link" onClick={handleNavClick}>Discover</Link>
          <Link to="/events?category=concerts" className="mobile-nav-link" onClick={handleNavClick}>Categories</Link>
          <Link to="/organizer/events/create" className="mobile-nav-link" onClick={handleNavClick}>For Organizers</Link>
          <Link to="/trust" className="mobile-nav-link" onClick={handleNavClick}>Trust & Safety</Link>
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
