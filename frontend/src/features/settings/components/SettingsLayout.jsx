import React from 'react';
import { Outlet, useLocation } from 'react-router-dom';

const settingsNavItems = [
  { to: '/settings/permissions', label: 'Permissions', icon: '🛡️', description: 'View and request access roles' },
  { to: '/settings/payment-methods', label: 'Payment Methods', icon: '💳', description: 'Saved cards and payment preferences' },
  { to: '/settings/delivery-preferences', label: 'Delivery Preferences', icon: '📧', description: 'Email and notification settings' },
  { to: '/settings/accessibility', label: 'Accessibility', icon: '♿', description: 'Accessibility preferences' },
  { to: '/settings/language', label: 'Language & Region', icon: '🌐', description: 'Language and regional formats' },
  { to: '/settings/device-localization', label: 'Device Sync', icon: '📱', description: 'Offline ticket sync and device token' },
];

const SettingsLayout = () => {
  const location = useLocation();

  const activeItem = settingsNavItems.find(
    (item) => item.to === location.pathname
  );

  return (
    <div className="min-h-screen bg-[#F7F8FA]">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        <div className="mb-8">
          <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">
            Settings
          </h1>
          <p className="mt-2 text-sm text-slate-500">
            Manage your account settings and preferences.
          </p>
        </div>

        {activeItem && (
          <div className="mb-6 inline-flex items-center gap-2 px-3 py-1.5 bg-[#FF6B6B]/10 text-[#FF6B6B] rounded-lg text-sm font-medium border border-[#FF6B6B]/20">
            <span>{activeItem.icon}</span>
            <span>{activeItem.label}</span>
          </div>
        )}

        {/* Main Content (full width — no sidebar) */}
        <main className="flex-1 min-w-0">
          <Outlet />
        </main>
      </div>
    </div>
  );
};

export default SettingsLayout;
