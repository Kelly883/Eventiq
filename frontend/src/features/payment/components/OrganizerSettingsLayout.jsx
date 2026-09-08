import React from 'react';
import { NavLink, Outlet, useLocation, Link } from 'react-router-dom';

const organizerSettingsNavItems = [
  {
    to: '/organizer/settings/payments',
    label: 'Payment Settings',
    icon: '💳',
    description: 'Gateway status, subaccounts, and payout methods',
  },
];

const OrganizerSettingsLayout = () => {
  const location = useLocation();

  const activeItem = organizerSettingsNavItems.find(
    (item) => item.to === location.pathname
  );

  return (
    <div className="min-h-screen bg-slate-50">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        <div className="mb-8">
          <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">
            Organizer Settings
          </h1>
          <p className="mt-2 text-sm text-slate-500">
            Manage your seller account, payments, and payout preferences.
          </p>
        </div>

        <div className="flex flex-col md:flex-row gap-8">
          {/* Sidebar Navigation */}
          <nav className="w-full md:w-64 flex-shrink-0" aria-label="Organizer settings">
            <div className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
              <ul className="divide-y divide-slate-100">
                {organizerSettingsNavItems.map((item) => (
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
              <div className="p-4 border-t border-slate-100 flex flex-col gap-2">
                <Link
                  to="/organizer/payouts"
                  className="flex items-center gap-2 text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors"
                >
                  <span>💰</span> View Payouts →
                </Link>
                <Link
                  to="/settings"
                  className="flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-slate-700 transition-colors"
                >
                  <span>⚙️</span> Account Settings →
                </Link>
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

export default OrganizerSettingsLayout;