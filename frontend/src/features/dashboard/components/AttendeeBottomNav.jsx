import React from 'react';
import { NavLink } from 'react-router-dom';
import Icon from './dashboard/Icon';
import '../dashboard.css';

/**
 * Shared bottom navigation used across every attendee dashboard page.
 *
 * - Fixed to the bottom of the viewport (mobile-first).
 * - Highlights the current page with the brand primary color (#FF6B6B).
 * - Inactive items use a muted gray color.
 * - Items: Home, Browse, Tickets, Calendar, Settings.
 */
const items = [
  { to: '/dashboard', label: 'Home', icon: 'layout' },
  { to: '/events', label: 'Browse', icon: 'browse' },
  { to: '/my-tickets', label: 'Tickets', icon: 'ticket' },
  { to: '/events/calendar', label: 'Calendar', icon: 'calendar' },
  { to: '/settings', label: 'Settings', icon: 'settings' },
];

export const AttendeeBottomNav = () => (
  <nav
    className="attendee-bottom-nav"
    aria-label="Attendee navigation"
  >
    {items.map((item) => (
      <NavLink
        key={item.to}
        to={item.to}
        end={true}
        className="attendee-nav-item"
        children={({ isActive }) => (
          <>
            <span
              className={`attendee-nav-item-icon ${isActive ? 'active' : ''}`}
            >
              <Icon
                name={item.icon}
                size={20}
                strokeWidth={2}
                className={isActive ? 'text-white' : 'text-[#6B7280]'}
              />
            </span>
            <span
              className={`attendee-nav-item-label ${isActive ? 'active' : ''}`}
            >
              {item.label}
            </span>
          </>
        )}
      />
    ))}
  </nav>
);

export default AttendeeBottomNav;
