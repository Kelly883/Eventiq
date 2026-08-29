import React from 'react';
import { useNotificationPreferences } from '../hooks/useNotificationPreferences';
import Skeleton from '../../../components/Skeleton';

const Toggle = ({ checked, onChange, disabled }) => (
  <button
    type="button"
    role="switch"
    aria-checked={checked}
    disabled={disabled}
    onClick={() => onChange(!checked)}
    className={`relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 ${
      checked ? 'bg-indigo-600' : 'bg-slate-200'
    } ${disabled ? 'opacity-50 cursor-not-allowed' : ''}`}
  >
    <span
      className={`inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform ${
        checked ? 'translate-x-5' : 'translate-x-0'
      }`}
    />
  </button>
);

const preferenceRows = [
  { key: 'pushOrderConfirmation', label: 'Order Confirmations', description: 'Get a push when you place an order for tickets.' },
  { key: 'pushEventReminder', label: 'Event Reminders', description: 'Reminders leading up to your upcoming events.' },
  { key: 'pushCheckinAlert', label: 'Check-in Alerts', description: 'Alerts when your tickets are scanned at the venue.' },
  { key: 'pushPromotionalOffers', label: 'Promotional Offers', description: 'Occasional offers and recommendations based on your interests.' },
];

const PushNotificationSettingsSection = () => {
  const { preferences, isLoading, isError, error, save, isSaving } = useNotificationPreferences();

  // Local optimistic copy so toggles feel instant; persisted on change.
  const [local, setLocal] = React.useState(null);

  React.useEffect(() => {
    if (preferences && local === null) {
      setLocal(preferences);
    }
  }, [preferences, local]);

  const handleToggle = (key, value) => {
    if (key === 'pushNotificationsEnabled') {
      setLocal((prev) => ({ ...prev, pushNotificationsEnabled: value }));
      save({ ...local, pushNotificationsEnabled: value });
      return;
    }
    setLocal((prev) => ({ ...prev, [key]: value }));
    const next = { ...local, [key]: value, pushNotificationsEnabled: true };
    setLocal(next);
    save(next);
  };

  if (isLoading) {
    return (
      <div className="space-y-4">
        <Skeleton variant="text" className="h-5 w-48" />
        <Skeleton variant="card" className="h-24" />
        <Skeleton variant="card" className="h-24" />
      </div>
    );
  }

  if (isError || !local) {
    return (
      <div className="p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
        {error?.response?.data?.message || 'Failed to load notification preferences. Please refresh and try again.'}
      </div>
    );
  }

  const masterEnabled = local.pushNotificationsEnabled;

  return (
    <div>
      <div className="flex items-center justify-between gap-4">
        <div>
          <h2 className="text-lg font-semibold text-slate-800">Push Notifications</h2>
          <p className="text-sm text-slate-500 mt-1">
            Choose which push notifications you want to receive on your device.
          </p>
        </div>
        <Toggle
          checked={masterEnabled}
          disabled={isSaving}
          onChange={(v) => handleToggle('pushNotificationsEnabled', v)}
        />
      </div>

      <div className="mt-4 divide-y divide-slate-100 border-t border-slate-100">
        {preferenceRows.map((row) => {
          const { key, label, description } = row;
          const value = masterEnabled ? !!local[key] : false;
          return (
            <div key={key} className="flex items-start justify-between gap-4 py-4">
              <div>
                <div className={`text-sm font-medium ${masterEnabled ? 'text-slate-800' : 'text-slate-400'}`}>{label}</div>
                <div className={`text-xs mt-0.5 ${masterEnabled ? 'text-slate-500' : 'text-slate-400'}`}>{description}</div>
              </div>
              <Toggle
                checked={value}
                disabled={!masterEnabled || isSaving}
                onChange={(v) => handleToggle(key, v)}
              />
            </div>
          );
        })}
      </div>

      <div className="mt-4 flex items-center justify-between gap-3">
        <p className="text-xs text-slate-400">
          {local.pushNotificationsEnabled
            ? 'Push notifications are enabled for your account.'
            : 'Push notifications are paused. You can re-enable them anytime.'}
        </p>
        {isSaving && (
          <span className="inline-flex items-center gap-1.5 text-xs text-indigo-600 font-medium">
            <span className="h-3 w-3 rounded-full border-2 border-indigo-200 border-t-indigo-600 animate-spin" />
            Saving…
          </span>
        )}
      </div>
    </div>
  );
};

export default PushNotificationSettingsSection;
