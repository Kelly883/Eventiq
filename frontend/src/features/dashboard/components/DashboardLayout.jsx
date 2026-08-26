import React from 'react';
import { NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom';
import { useAuthContext } from '../../auth/context/AuthContext';

const DashboardLayout = () => {
  const location = useLocation();
  const navigate = useNavigate();
  const { user } = useAuthContext();

  const roles = user?.roles?.map((r) => r.name) || [];
  const isOrganizer = roles.includes('organizer');
  const isAdmin = roles.includes('admin');

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
    to: '/dashboard/user',
    label: 'My Dashboard',
    icon: '👤',
    description: 'Personal overview',
  });

  const activeItem = dashboardNavItems.find((item) => item.to === location.pathname);

  const getPageTitle = () => {
    if (location.pathname === '/dashboard/organizer') return 'Organizer Dashboard';
    if (location.pathname === '/dashboard/user') return 'My Dashboard';
    return 'Dashboard';
  };

  return (
    <div className="min-h-screen bg-slate-50">
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
  );
};

export default DashboardLayout;
