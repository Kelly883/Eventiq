import React from 'react';
import { Link } from 'react-router-dom';

const Footer = () => {
  return (
    <footer className="footer">
      <div className="footer-container">
        <div className="footer-grid">
          <div className="footer-column">
            <h4 className="footer-heading">Discover</h4>
            <ul className="footer-links">
              <li><Link to="/events">Events</Link></li>
              <li><Link to="/events?category=concerts">Concerts</Link></li>
              <li><Link to="/events?category=festivals">Festivals</Link></li>
              <li><Link to="/events?category=comedy">Comedy</Link></li>
              <li><Link to="/events?category=sports">Sports</Link></li>
            </ul>
          </div>

          <div className="footer-column">
            <h4 className="footer-heading">For Organizers</h4>
            <ul className="footer-links">
              <li><Link to="/organizer/events/create">Create an Event</Link></li>
              <li><Link to="/dashboard/organizer">Organizer Dashboard</Link></li>
              <li><Link to="/organizer/payouts">Payouts</Link></li>
            </ul>
          </div>

          <div className="footer-column">
            <h4 className="footer-heading">Support</h4>
            <ul className="footer-links">
              <li><Link to="/trust">Trust & Safety</Link></li>
              <li><Link to="/refund-policy">Refund Policy</Link></li>
              <li><Link to="/terms">Terms of Service</Link></li>
            </ul>
          </div>

          <div className="footer-column">
            <h4 className="footer-heading">Company</h4>
            <ul className="footer-links">
              <li><Link to="/about">About</Link></li>
              <li><Link to="/contact">Contact Us</Link></li>
              <li><Link to="/help">Help Center</Link></li>
            </ul>
          </div>
        </div>

        <div className="footer-bottom">
          <div className="footer-brand">
            <span className="logo-icon">◆</span>
            <span className="logo-text">EVENTIQ</span>
          </div>
          <div className="footer-social">
            <a href="https://x.com" target="_blank" rel="noopener noreferrer" aria-label="Twitter">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
              </svg>
            </a>
            <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.148-3.227 1.692-4.771 4.918-4.919 1.266-.058 1.645-.07 4.85-.07zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
              </svg>
            </a>
            <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
              </svg>
            </a>
          </div>
          <p className="footer-copyright">© 2026 Eventiq. All rights reserved.</p>
        </div>
      </div>
    </footer>
  );
};

export default Footer;
