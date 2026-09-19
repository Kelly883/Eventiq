import React from 'react';
import { Link } from 'react-router-dom';
import Icon from './Icon';

const MobileNav = ({ user, items = [], isOpen, onClose, onLogout }) => {
  if (!isOpen) return null;

  return (
    <>
      <div className="mobile-nav-backdrop" onClick={onClose} aria-hidden="true" />
      <nav className="mobile-nav-drawer" aria-label="Mobile navigation">
        <div className="mobile-nav-header">
          <span className="mobile-nav-title">Menu</span>
          <button
            type="button"
            className="mobile-nav-close"
            onClick={onClose}
            aria-label="Close navigation"
          >
            <Icon name="close" size={22} />
          </button>
        </div>
        <div className="mobile-nav-items">
          {items.map((item) => (
            <Link
              key={item.to}
              to={item.to}
              className="mobile-nav-item"
              onClick={onClose}
            >
              <span className="mobile-nav-item-icon">
                <Icon name={item.icon} size={20} />
              </span>
              <span className="mobile-nav-item-label">{item.label}</span>
              <Icon name="arrow-right" size={16} className="mobile-nav-item-chevron" />
            </Link>
          ))}
        </div>
        {user && (
          <div className="mobile-nav-footer">
            <Link to="/settings" className="mobile-nav-user" onClick={onClose}>
              <div className="mobile-nav-avatar">
                <Icon name="user" size={20} />
              </div>
              <div className="mobile-nav-user-info">
                <span className="mobile-nav-user-name">{user.name || 'User'}</span>
                <span className="mobile-nav-user-email">{user.email}</span>
              </div>
            </Link>
            <button
              type="button"
              className="mobile-nav-logout"
              onClick={() => {
                onLogout?.();
                onClose();
              }}
              aria-label="Sign out"
            >
              <span className="mobile-nav-logout-icon">
                <Icon name="logout" size={20} />
              </span>
              Sign Out
            </button>
          </div>
        )}
      </nav>
    </>
  );
};

export default MobileNav;
