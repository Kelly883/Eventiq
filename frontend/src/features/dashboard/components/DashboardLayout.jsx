import React, { useState } from 'react';
import { NavLink, Outlet, useLocation } from 'react-router-dom';
import { useAuthContext } from '../../auth/context/AuthContext';
import Icon from './dashboard/Icon';
import MobileNav from './dashboard/MobileNav';
import '../dashboard.css';

const DashboardLayout = () => {
  const location = useLocation();
  const { user } = useAuthContext();
  const [mobileNavOpen, setMobileNavOpen] = useState(false);

  const roles = user?.roles?.map((r) => r.name) || [];
  const isOrganizer = roles.includes('organizer');
  const isAdmin = roles.includes('admin');
  const isVenueStaff = roles.includes('venue_staff');

  const dashboardNavItems = [];

  if (isOrganizer) {
    dashboardNavItems.push({
      to: '/dashboard/organizer',
      label: 'Organizer',
      icon: 'dashboard',
      description: 'Events and analytics',
    });
    dashboardNavItems.push({
      to: '/organizer/payouts',
      label: 'Payouts',
      icon: 'browse',
      description: 'Your earnings and payout history for your events only.',
    });
  }

  if (isAdmin) {
    dashboardNavItems.push({
      to: '/admin',
      label: 'Admin',
      icon: 'settings',
      description: 'Platform management',
    });
    dashboardNavItems.push({
      to: '/admin/settlements/dashboard',
      label: 'Settlements',
      icon: 'dashboard',
      description: 'Platform-wide refund and settlement management. Different from organizer payouts.',
    });
  }

  dashboardNavItems.push({
    to: '/dashboard',
    label: 'My Dashboard',
    icon: 'dashboard',
    description: 'Personal overview',
  });

  const getPageTitle = () => {
    if (location.pathname === '/dashboard/organizer') return 'Organizer Dashboard';
    if (location.pathname === '/dashboard') return 'My Dashboard';
    return 'Dashboard';
  };

  const mobileNavItems = dashboardNavItems.map(({ to, label, icon }) => ({ to, label, icon }));

  return (
    <div className="dashboard-layout">
      {/* Mobile Header */}
      <header className="dashboard-mobile-header">
        <span className="dashboard-brand-name">EventIQ</span>
        <div className="dashboard-header-actions">
          <button
            type="button"
            className="dashboard-icon-btn"
            aria-label="Open navigation"
            onClick={() => setMobileNavOpen(true)}
          >
            <Icon name="menu" size={24} />
          </button>
        </div>
      </header>

      {/* Mobile Navigation Drawer */}
      <MobileNav
        user={user}
        items={mobileNavItems}
        isOpen={mobileNavOpen}
        onClose={() => setMobileNavOpen(false)}
      />

      {/* Persistent Sidebar (desktop) */}
      <aside className="dashboard-sidebar">
        <div className="dashboard-sidebar-header">
          <h2 className="dashboard-sidebar-title">EventIQ</h2>
          <p className="dashboard-sidebar-subtitle">Your dashboard</p>
        </div>
        <nav className="dashboard-sidebar-nav" aria-label="Dashboard">
          {dashboardNavItems.map((item) => {
            const isActive = item.exact
              ? location.pathname === item.to
              : location.pathname.startsWith(item.to);
            return (
              <NavLink
                key={item.to}
                to={item.to}
                className={({ isActive: active }) =>
                  `dashboard-sidebar-item ${active || isActive ? 'active' : ''}`
                }
              >
                <span className="dashboard-sidebar-item-icon">
                  <Icon name={item.icon} size={20} />
                </span>
                <span className="dashboard-sidebar-item-label">{item.label}</span>
              </NavLink>
            );
          })}
          {(isVenueStaff || isOrganizer || isAdmin) && (
            <>
              <div className="dashboard-sidebar-section">
                <span className="dashboard-sidebar-section-title">Staff</span>
              </div>
              <NavLink
                to="/venue/dashboard"
                className={({ isActive }) =>
                  `dashboard-sidebar-item ${isActive ? 'active' : ''}`
                }
              >
                <span className="dashboard-sidebar-item-icon">
                  <Icon name="browse" size={20} />
                </span>
                <span className="dashboard-sidebar-item-label">Venue Dashboard</span>
              </NavLink>
              <NavLink
                to="/venue/events"
                className={({ isActive }) =>
                  `dashboard-sidebar-item ${isActive ? 'active' : ''}`
                }
              >
                <span className="dashboard-sidebar-item-icon">
                  <Icon name="calendar" size={20} />
                </span>
                <span className="dashboard-sidebar-item-label">Check-In Events</span>
              </NavLink>
            </>
          )}
        </nav>
      </aside>

      {/* Main Content */}
      <div className="dashboard-main">
        <div className="dashboard-container">
          <div className="dashboard-header">
            <h1 className="dashboard-title">
              {getPageTitle()}
            </h1>
            <p className="dashboard-subtitle">
              {isOrganizer && 'Manage your events, track sales, and view analytics.'}
              {isAdmin && !isOrganizer && 'Access platform management tools.'}
              {!isOrganizer && !isAdmin && 'View your tickets and account activity.'}
            </p>
          </div>

          {dashboardNavItems.length > 1 && (
            <nav className="dashboard-tabs" aria-label="Dashboard sections">
              {dashboardNavItems.map((item) => (
                <NavLink
                  key={item.to}
                  to={item.to}
                  className={({ isActive }) =>
                    `dashboard-tab ${isActive ? 'active' : ''}`
                  }
                >
                  <span className="dashboard-tab-icon">
                    <Icon name={item.icon} size={16} />
                  </span>
                  <span className="dashboard-tab-label">{item.label}</span>
                </NavLink>
              ))}
            </nav>
          )}

          <Outlet />
        </div>
      </div>
    </div>
  );
};

export default DashboardLayout;
