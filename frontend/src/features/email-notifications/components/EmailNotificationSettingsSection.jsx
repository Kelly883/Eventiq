import React from 'react';
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

const emailPreferenceRows = [
  { key: 'emailOrderConfirmation', label: 'Order Confirmations', description: 'Get an email when you place an order for tickets.' },
  { key: 'emailEventReminder', label: 'Event Reminders', description: 'Reminders leading up to your upcoming events.' },
  { key: 'emailCheckinAlert', label: 'Check-in Alerts', description: 'Alerts when your tickets are scanned at the venue.' },
  { key: 'emailPromotionalOffers', label: 'Promotional Offers', description: 'Occasional offers and recommendations based on your interests.' },
  { key: 'emailTicketDelivery', label: 'Ticket Delivery', description: 'Notifications when your tickets are delivered.' },
];

const EmailNotificationSettingsSection = () => {
  const [local, setLocal] = React.useState(null);
  const [isLoading, setIsLoading] = React.useState(true);
  const [isSaving, setIsSaving] = React.useState(false);
  const [error, setError] = React.useState(null);

  React.useEffect(() => {
    let cancelled = false;
    setIsLoading(true);
    fetch('/api/push-notifications/preferences', { credentials: 'include' })
      .then((r) => r.json())
      .then((data) => {
        if (cancelled) return;
        setLocal({
          emailNotificationsEnabled: true,
          emailOrderConfirmation: true,
          emailEventReminder: true,
          emailCheckinAlert: true,
          emailPromotionalOffers: false,
          emailTicketDelivery: true,
          ...(data?.data || data || {}),
        });
      })
      .catch((err) => {
        if (cancelled) return;
        setError(err.message || 'Failed to load email preferences.');
      })
      .finally(() => {
        if (!cancelled) setIsLoading(false);
      });
    return () => { cancelled = true; };
  }, []);

  const handleToggle = (key, value) => {
    setLocal((prev) => ({ ...prev, [key]: value }));
    setIsSaving(true);
    fetch('/api/push-notifications/preferences', {
      method: 'PUT',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ ...local, [key]: value }),
    })
      .then(() => { /* optimistic already applied */ })
      .catch(() => {
        setLocal((prev) => ({ ...prev, [key]: !value }));
      })
      .finally(() => setIsSaving(false));
  };

  if (isLoading) {
    return (
      <div className="space-y-4">
        <Skeleton variant="text" className="h-5 w-48" />
        <Skeleton variant="card" className="h-20" />
        <Skeleton variant="card" className="h-20" />
      </div>
    );
  }

  if (error || !local) {
    return (
      <div className="p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
        {error || 'Failed to load email preferences. Please refresh and try again.'}
      </div>
    );
  }

  const masterEnabled = local.emailNotificationsEnabled;

  return (
    <div>
      <div className="flex items-center justify-between gap-4">
        <div>
          <h3 className="text-base font-semibold text-slate-800">Email Notifications</h3>
          <p className="text-sm text-slate-500 mt-1">
            Choose which emails you want to receive at your registered address.
          </p>
        </div>
        <Toggle
          checked={masterEnabled}
          disabled={isSaving}
          onChange={(v) => handleToggle('emailNotificationsEnabled', v)}
        />
      </div>

      <div className="mt-4 divide-y divide-slate-100 border-t border-slate-100">
        {emailPreferenceRows.map((row) => {
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
          {masterEnabled
            ? 'Email notifications are enabled for your account.'
            : 'Email notifications are paused. You can re-enable them anytime.'}
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

export default EmailNotificationSettingsSection;