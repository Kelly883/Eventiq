import React from 'react';
import PushNotificationSettingsSection from '../../push-notifications/components/PushNotificationSettingsSection';
import EmailNotificationSettingsSection from '../../email-notifications/components/EmailNotificationSettingsSection';

const DeliverySettingsPage = () => {
  return (
    <div className="max-w-3xl mx-auto p-6 md:p-10 space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-slate-900">Delivery Preferences</h1>
        <p className="text-sm text-slate-500 mt-1">Manage how you receive tickets and notifications.</p>
      </div>

      <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
        <h2 className="text-lg font-semibold text-slate-800 flex items-start gap-2">
          <span className="text-2xl text-indigo-600">📧</span>
          Email Notifications
        </h2>
        <p className="text-sm text-slate-500 mt-1">Configure email delivery for tickets and updates.</p>
        <EmailNotificationSettingsSection />
      </div>

      <div className="mt-6 border-t border-slate-200"></div>

      <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
        <h2 className="text-lg font-semibold text-slate-800 flex items-start gap-2">
          <span className="text-2xl text-indigo-600">📱</span>
          Push Notifications
        </h2>
        <PushNotificationSettingsSection />
      </div>
    </div>
  );
};

export default DeliverySettingsPage;
