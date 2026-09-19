import React, { useState, useRef, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuthContext } from '../../../auth/context/AuthContext';
import Icon from './Icon';

const AccountDropdown = () => {
  const [open, setOpen] = useState(false);
  const { user, logout } = useAuthContext();
  const navigate = useNavigate();
  const containerRef = useRef(null);

  useEffect(() => {
    const handleClickOutside = (e) => {
      if (containerRef.current && !containerRef.current.contains(e.target)) {
        setOpen(false);
      }
    };
    if (open) {
      document.addEventListener('mousedown', handleClickOutside);
      return () => document.removeEventListener('mousedown', handleClickOutside);
    }
  }, [open]);

  if (!user) return null;

  const initials = user.name
    ? user.name.trim().charAt(0).toUpperCase()
    : user.email?.charAt(0).toUpperCase() || 'U';

  const handleLogout = () => {
    logout();
    setOpen(false);
    navigate('/login', { replace: true });
  };

  return (
    <div ref={containerRef} style={{ position: 'relative' }}>
      <button
        type="button"
        className="dashboard-account-btn"
        onClick={() => setOpen((v) => !v)}
        aria-haspopup="menu"
        aria-expanded={open}
      >
        <span className="dashboard-account-avatar" aria-hidden="true">
          {initials}
        </span>
        <span className="dashboard-account-name">{user.name || 'Account'}</span>
        <Icon name={open ? 'chevron-down' : 'chevron-down'} size={16} />
      </button>

      {open && (
        <div className="dashboard-account-menu" role="menu">
          <div className="dashboard-account-menu-header">
            <div className="dashboard-account-menu-name">{user.name || 'User'}</div>
            <div className="dashboard-account-menu-email">{user.email}</div>
          </div>
          <Link
            to="/settings"
            className="dashboard-account-menu-item"
            onClick={() => setOpen(false)}
            role="menuitem"
          >
            <Icon name="settings" size={18} />
            Settings
          </Link>
          <Link
            to="/my-tickets"
            className="dashboard-account-menu-item"
            onClick={() => setOpen(false)}
            role="menuitem"
          >
            <Icon name="ticket" size={18} />
            My Tickets
          </Link>
          <div className="dashboard-account-menu-divider" />
          <button
            type="button"
            className="dashboard-account-menu-item danger"
            onClick={handleLogout}
            role="menuitem"
          >
            <Icon name="logout" size={18} />
            Sign Out
          </button>
        </div>
      )}
    </div>
  );
};

export default AccountDropdown;
