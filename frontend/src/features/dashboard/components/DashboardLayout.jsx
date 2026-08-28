import React from 'react';
import { NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom';
import { useAuthContext } from '../../auth/context/AuthContext';

const dashboardSidebarItems = [
  { to: '/dashboard', label: 'Dashboard', icon: '👤', exact: true },
  { to: '/my-tickets', label: 'My Tickets', icon: '🎫' },
  { to: '/my-tickets/status', label: 'Check Ticket', icon: '🔍' },
  { to: '/events', label: 'Browse Events', icon: '📋' },
  { to: '/settings', label: 'Settings', icon: '⚙️' },
];

const DashboardLayout = () => {
  const location = useLocation();
  const navigate = useNavigate();
  const { user } = useAuthContext();

  const roles = user?.roles?.map((r) => r.name) || [];
  const isOrganizer = roles.includes('organizer');
  const isAdmin = roles.includes('admin');
  const isVenueStaff = roles.includes('venue_staff');

  const dashboardNavItems = [];

  if (isOrganizer) {
    dashboardNavItems.push({
      to: '/dashboard/organizer',
      label: 'Organizer',
      icon: '💼',
      description: 'Events and analytics',
    });
  }

  if (isAdmin) {
    dashboardNavItems.push({
      to: '/admin',
      label: 'Admin',
      icon: '🛡️',
      description: 'Platform management',
    });
  }

  dashboardNavItems.push({
    to: '/dashboard',
    label: 'My Dashboard',
    icon: '👤',
    description: 'Personal overview',
  });

  const activeItem = dashboardNavItems.find((item) => item.to === location.pathname);

  const getPageTitle = () => {
    if (location.pathname === '/dashboard/organizer') return 'Organizer Dashboard';
    if (location.pathname === '/dashboard') return 'My Dashboard';
    return 'Dashboard';
  };

  return (
    <div className="min-h-screen bg-slate-50 flex">
      {/* Persistent Sidebar */}
      <aside className="w-64 bg-white border-r border-slate-200 flex-shrink-0 hidden md:block">
        <div className="p-4 border-b border-slate-100">
          <h2 className="text-lg font-bold text-slate-900">Eventiq</h2>
          <p className="text-xs text-slate-500">Your dashboard</p>
        </div>
        <nav className="p-3 space-y-1">
          {dashboardSidebarItems.map((item) => {
            const isActive = item.exact
              ? location.pathname === item.to
              : location.pathname.startsWith(item.to);
            return (
              <NavLink
                key={item.to}
                to={item.to}
                className={({ isActive: active }) =>
                  `flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
                    active || isActive
                      ? 'bg-indigo-50 text-indigo-700'
                      : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                  }`
                }
              >
                <span className="text-lg">{item.icon}</span>
                <span>{item.label}</span>
              </NavLink>
            );
          })}
          {(isVenueStaff || isOrganizer || isAdmin) && (
            <>
              <div className="pt-4 pb-2">
                <span className="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">
                  Staff
                </span>
              </div>
              <NavLink
                to="/venue/dashboard"
                className={({ isActive }) =>
                  `flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
                    isActive
                      ? 'bg-indigo-50 text-indigo-700'
                      : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                  }`
                }
              >
                <span className="text-lg">🎯</span>
                <span>Venue Dashboard</span>
              </NavLink>
              <NavLink
                to="/venue/events"
                className={({ isActive }) =>
                  `flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
                    isActive
                      ? 'bg-indigo-50 text-indigo-700'
                      : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                  }`
                }
              >
                <span className="text-lg">📋</span>
                <span>Check-In Events</span>
              </NavLink>
              <NavLink
                to="/check-in"
                className={({ isActive }) =>
                  `flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
                    isActive
                      ? 'bg-indigo-50 text-indigo-700'
                      : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                  }`
                }
              >
                <span className="text-lg">🎟️</span>
                <span>Check-In Desk</span>
              </NavLink>
              <NavLink
                to="/venue/events"
                className={({ isActive }) =>
                  `flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
                    isActive
                      ? 'bg-indigo-50 text-indigo-700'
                      : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                  }`
                }
              >
                <span className="text-lg">📷</span>
                <span>Gate Scanner</span>
              </NavLink>
            </>
          )}
        </nav>
      </aside>

      {/* Main Content */}
      <div className="flex-1 min-w-0">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
          <div className="mb-8">
            <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">
              {getPageTitle()}
            </h1>
            <p className="mt-2 text-sm text-slate-500">
              {isOrganizer && 'Manage your events, track sales, and view analytics.'}
              {isAdmin && !isOrganizer && 'Access platform management tools.'}
              {!isOrganizer && !isAdmin && 'View your tickets and account activity.'}
            </p>
          </div>

          {dashboardNavItems.length > 1 && (
            <div className="mb-6">
              <nav className="flex flex-wrap gap-2">
                {dashboardNavItems.map((item) => (
                  <NavLink
                    key={item.to}
                    to={item.to}
                    className={({ isActive }) =>
                      `inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                        isActive
                          ? 'bg-indigo-600 text-white shadow-sm'
                          : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50 hover:border-slate-300'
                      }`
                    }
                  >
                    <span>{item.icon}</span>
                    <span>{item.label}</span>
                  </NavLink>
                ))}
              </nav>
            </div>
          )}

          <Outlet />
        </div>
      </div>
    </div>
  );
};

export default DashboardLayout;
