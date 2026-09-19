import React, { useState } from 'react';
import { NavLink, Outlet, useLocation } from 'react-router-dom';
import { useAuthContext } from '../../auth/context/AuthContext';
import Icon from './dashboard/Icon';
import MobileNav from './dashboard/MobileNav';
import AccountDropdown from './dashboard/AccountDropdown';
import BottomNav from './dashboard/BottomNav';
import BrandLogo from '../../common/components/BrandLogo';
import '../dashboard.css';

const DashboardLayout = () => {
  const location = useLocation();
  const { user, logout } = useAuthContext();
  const [mobileNavOpen, setMobileNavOpen] = useState(false);

  const roles = user?.roles?.map((r) => r.name) || [];
  const isOrganizer = roles.includes('organizer');
  const isAdmin = roles.includes('admin');
  const isVenueStaff = roles.includes('venue_staff');

  // ── Desktop top header nav — visible on all dashboard pages ──
  const desktopNavItems = [
    { to: '/dashboard', label: 'Dashboard', icon: 'layout', end: true },
    { to: '/events', label: 'Browse Events', icon: 'browse' },
    { to: '/events/calendar', label: 'Calendar', icon: 'calendar' },
    { to: '/my-tickets', label: 'My Tickets', icon: 'ticket' },
  ];

  // ── Sidebar navigation — user + role-based sections ──
  const sidebarNavItems = [];

  if (isOrganizer) {
    sidebarNavItems.push({
      to: '/dashboard/organizer',
      label: 'Organizer Dashboard',
      icon: 'dashboard',
      description: 'Events and analytics',
    });
    sidebarNavItems.push({
      to: '/organizer/payouts',
      label: 'Payouts',
      icon: 'credit',
      description: 'Earnings and payout history',
    });
  }

  if (isAdmin) {
    sidebarNavItems.push({
      to: '/admin',
      label: 'Admin',
      icon: 'settings',
      description: 'Platform management',
    });
    sidebarNavItems.push({
      to: '/admin/settlements/dashboard',
      label: 'Settlements',
      icon: 'dashboard',
      description: 'Platform-wide settlements',
    });
  }

  sidebarNavItems.push({
    to: '/dashboard',
    label: 'My Dashboard',
    icon: 'layout',
    description: 'Personal overview',
    exact: true,
  });

  // ── Staff section (sidebar) ──
  const staffSectionItems = [];
  if (isVenueStaff || isOrganizer || isAdmin) {
    staffSectionItems.push({
      to: '/venue/dashboard',
      label: 'Venue Dashboard',
      icon: 'browse',
    });
    staffSectionItems.push({
      to: '/venue/events',
      label: 'Check-In Events',
      icon: 'calendar',
    });
  }

  // ── Mobile bottom nav ──
  const bottomNavItems = [
    { to: '/dashboard', label: 'Home', icon: 'layout', end: true },
    { to: '/events', label: 'Browse', icon: 'browse' },
    { to: '/my-tickets', label: 'Tickets', icon: 'ticket' },
    { to: '/events/calendar', label: 'Calendar', icon: 'calendar' },
    { to: '/settings', label: 'Settings', icon: 'settings' },
  ];

  // ── Mobile drawer items (expanded set) ──
  const mobileNavItems = [
    ...desktopNavItems,
    { to: '/settings', label: 'Settings', icon: 'settings' },
  ];
  if (isOrganizer) {
    mobileNavItems.push({ to: '/dashboard/organizer', label: 'Organizer', icon: 'dashboard' });
  }
  if (isAdmin) {
    mobileNavItems.push({ to: '/admin', label: 'Admin', icon: 'settings' });
  }

  const getPageTitle = () => {
    if (location.pathname === '/dashboard/organizer') return 'Organizer Dashboard';
    if (location.pathname === '/dashboard') return 'My Dashboard';
    return 'Dashboard';
  };

  const handleSidebarLogout = () => {
    logout();
    window.location.href = '/login';
  };

  return (
    <div className="dashboard-layout">
      {/* ── Mobile Header (< 768px) ── */}
      <header className="dashboard-mobile-header">
        <NavLink to="/" className="dashboard-brand" aria-label="eventIQ home">
          <BrandLogo variant="light" />
        </NavLink>
        <div className="dashboard-mobile-header-actions">
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

      {/* ── Mobile Navigation Drawer ── */}
      <MobileNav
        user={user}
        items={mobileNavItems}
        isOpen={mobileNavOpen}
        onClose={() => setMobileNavOpen(false)}
      />

      {/* ── Desktop Top Header (>= 768px) ── */}
      <header className="dashboard-top-header">
        <div className="dashboard-top-header-left">
          <nav className="dashboard-top-header-nav" aria-label="Primary">
            {desktopNavItems.map((item) => (
              <NavLink
                key={item.to}
                to={item.to}
                end={item.end}
                className={({ isActive }) =>
                  `dashboard-top-header-link ${isActive ? 'active' : ''}`
                }
              >
                <span className="dashboard-top-header-link-icon">
                  <Icon name={item.icon} size={18} />
                </span>
                {item.label}
              </NavLink>
            ))}
          </nav>
        </div>
        <div className="dashboard-top-header-right">
          <AccountDropdown />
        </div>
      </header>

      {/* ── Body: sidebar + main ── */}
      <div className="dashboard-body">
        {/* ── Sidebar (desktop, >= 768px) ── */}
        <aside className="dashboard-sidebar" aria-label="Dashboard sections">
          <div className="dashboard-sidebar-header">
            <NavLink to="/" aria-label="eventIQ home">
              <BrandLogo variant="light" />
            </NavLink>
          </div>
          <nav className="dashboard-sidebar-nav" aria-label="Dashboard">
            {sidebarNavItems.map((item) => {
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
            {staffSectionItems.length > 0 && (
              <>
                <div className="dashboard-sidebar-section">
                  <span className="dashboard-sidebar-section-title">Staff</span>
                </div>
                {staffSectionItems.map((item) => (
                  <NavLink
                    key={item.to}
                    to={item.to}
                    className={({ isActive }) =>
                      `dashboard-sidebar-item ${isActive ? 'active' : ''}`
                    }
                  >
                    <span className="dashboard-sidebar-item-icon">
                      <Icon name={item.icon} size={20} />
                    </span>
                    <span className="dashboard-sidebar-item-label">{item.label}</span>
                  </NavLink>
                ))}
              </>
            )}
          </nav>
          <div className="dashboard-sidebar-footer">
            <button
              type="button"
              className="dashboard-sidebar-logout"
              onClick={handleSidebarLogout}
              aria-label="Sign out"
            >
              <Icon name="logout" size={20} />
              Sign Out
            </button>
          </div>
        </aside>

        {/* ── Main Content ── */}
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

            {sidebarNavItems.length > 1 && (
              <nav className="dashboard-tabs" aria-label="Dashboard sections">
                {sidebarNavItems.map((item) => (
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

      {/* ── Mobile Bottom Navigation (< 768px) ── */}
      <BottomNav items={bottomNavItems} />
    </div>
  );
};

export default DashboardLayout;
