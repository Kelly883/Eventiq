import React from 'react';
import { NavLink, Outlet, useLocation, Link } from 'react-router-dom';

const adminNavItems = [
  { to: '/admin/roles', label: 'Roles', icon: '🛡️', description: 'Manage admin roles and permissions', group: 'management' },
  { to: '/admin/fraud/dashboard', label: 'Fraud Detection', icon: '🕵️', description: 'Monitor flagged transactions', group: 'management' },
  { to: '/admin/delivery/dashboard', label: 'Delivery', icon: '📦', description: 'Ticket delivery management', group: 'management' },
  { to: '/admin/analytics', label: 'Analytics', icon: '📊', description: 'Platform-wide analytics', group: 'settings' },
  { to: '/admin/settings/email-templates', label: 'Email Templates', icon: '✉️', description: 'Manage notification templates', group: 'settings' },
];

const AdminLayout = () => {
  const location = useLocation();

  const activeItem = adminNavItems.find(
    (item) => item.to === location.pathname || location.pathname.startsWith(item.to + '/')
  );

  const managementItems = adminNavItems.filter(item => item.group === 'management');
  const settingsItems = adminNavItems.filter(item => item.group === 'settings');

  return (
    <div className="min-h-screen bg-slate-50">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        {/* Breadcrumb */}
        <nav className="mb-4 flex items-center gap-2 text-sm" aria-label="Breadcrumb">
          <Link
            to="/admin/roles"
            className="text-slate-500 hover:text-slate-700 font-medium"
          >
            Admin
          </Link>
          <span className="text-slate-400">/</span>
          <span className="text-slate-700 font-medium">
            {activeItem?.label || 'Dashboard'}
          </span>
        </nav>

        <div className="mb-8">
          <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">
            Admin Panel
          </h1>
          <p className="mt-2 text-sm text-slate-500">
            Manage platform settings, users, and security.
          </p>
        </div>

        <div className="flex flex-col md:flex-row gap-8">
          {/* Sidebar Navigation */}
          <nav className="w-full md:w-64 flex-shrink-0">
            <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
              {/* Management Section */}
              <div>
                <div className="px-4 py-2 bg-slate-50 border-b border-slate-100">
                  <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    Management
                  </span>
                </div>
                <ul className="divide-y divide-slate-100">
                  {managementItems.map((item) => (
                    <li key={item.to}>
                      <NavLink
                        to={item.to}
                        className={({ isActive }) =>
                          `flex items-center gap-3 px-4 py-3 text-sm font-medium transition-colors ${
                            isActive
                              ? 'bg-indigo-50 text-indigo-700 border-l-4 border-indigo-600'
                              : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 border-l-4 border-transparent'
                          }`
                        }
                      >
                        <span className="text-lg">{item.icon}</span>
                        <div>
                          <div className="font-semibold">{item.label}</div>
                          <div className="text-xs text-slate-400 font-normal">{item.description}</div>
                        </div>
                      </NavLink>
                    </li>
                  ))}
                </ul>
              </div>

              {/* Settings Section */}
              <div>
                <div className="px-4 py-2 bg-slate-50 border-b border-slate-100">
                  <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    Settings
                  </span>
                </div>
                <ul className="divide-y divide-slate-100">
                  {settingsItems.map((item) => (
                    <li key={item.to}>
                      <NavLink
                        to={item.to}
                        className={({ isActive }) =>
                          `flex items-center gap-3 px-4 py-3 text-sm font-medium transition-colors ${
                            isActive
                              ? 'bg-indigo-50 text-indigo-700 border-l-4 border-indigo-600'
                              : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 border-l-4 border-transparent'
                          }`
                        }
                      >
                        <span className="text-lg">{item.icon}</span>
                        <div>
                          <div className="font-semibold">{item.label}</div>
                          <div className="text-xs text-slate-400 font-normal">{item.description}</div>
                        </div>
                      </NavLink>
                    </li>
                  ))}
                </ul>
              </div>
            </div>
          </nav>

          {/* Main Content */}
          <main className="flex-1 min-w-0">
            {activeItem && (
              <div className="mb-4 md:hidden">
                <span className="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-50 text-indigo-700 rounded-lg text-sm font-medium">
                  {activeItem.icon} {activeItem.label}
                </span>
              </div>
            )}
            <Outlet />
          </main>
        </div>
      </div>
    </div>
  );
};

export default AdminLayout;
