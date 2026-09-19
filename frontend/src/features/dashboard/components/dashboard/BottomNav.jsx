import React from 'react';
import { NavLink } from 'react-router-dom';
import Icon from './Icon';

const BottomNav = ({ items = [] }) => {
  return (
    <nav className="dashboard-bottom-nav" aria-label="Primary navigation">
      {items.map((item) => (
        <NavLink
          key={item.to}
          to={item.to}
          end={item.end}
          className={({ isActive }) =>
            `bottom-nav-item ${isActive ? 'active' : ''}`
          }
        >
          <span className="bottom-nav-item-icon">
            <Icon name={item.icon} size={22} />
          </span>
          <span className="bottom-nav-item-label">{item.label}</span>
        </NavLink>
      ))}
    </nav>
  );
};

export default BottomNav;
