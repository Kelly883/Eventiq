import React from 'react';
import { NavLink } from 'react-router-dom';
import Icon from './dashboard/Icon';
import '../dashboard.css';

/**
 * Shared bottom navigation used across every attendee dashboard page.
 *
 * - Fixed to the bottom of the viewport (all screen sizes).
 * - Highlights the current page with the brand primary color (#FF6B6B).
 * - Inactive items use a muted gray color.
 * - Items: Home, Browse, Tickets, Calendar, Settings.
 */
const items = [
  { to: '/dashboard', label: 'Home', icon: 'layout', end: true },
  { to: '/events', label: 'Browse', icon: 'browse', end: false },
  { to: '/my-tickets', label: 'Tickets', icon: 'ticket', end: false },
  { to: '/events/calendar', label: 'Calendar', icon: 'calendar', end: false },
  { to: '/settings', label: 'Settings', icon: 'settings', end: false },
];

export const AttendeeBottomNav = () => (
  <nav className="attendee-bottom-nav" aria-label="Attendee navigation">
    {items.map((item) => (
      <NavLink
        key={item.to}
        to={item.to}
        end={item.end}
        className={({ isActive }) => `attendee-nav-item ${isActive ? 'active' : ''}`}
      >
        {({ isActive }) => (
          <>
            <span className={`attendee-nav-item-icon ${isActive ? 'active' : ''}`}>
              <Icon
                name={item.icon}
                size={20}
                strokeWidth={2}
                className={isActive ? 'text-white' : 'text-[#6B7280]'}
              />
            </span>
            <span className={`attendee-nav-item-label ${isActive ? 'active' : ''}`}>
              {item.label}
            </span>
          </>
        )}
      </NavLink>
    ))}
  </nav>
);

export default AttendeeBottomNav;
