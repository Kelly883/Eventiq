import React, { useState } from 'react';
import { useAdminDashboard } from '../hooks/useAdminDashboard';
import { MetricCard, ActivityFeed, AlertsSection, QuickStatsSection, NavigationTiles } from '../components/dashboard';
import { requestNotificationPermissionAndToken } from '../../../config/firebase';
import { showToast } from '../../../lib/api';

const AdminDashboardPage = () => {
  const { loading, error, metrics, activity, alerts, quickStats } = useAdminDashboard();
  const [notificationStatus, setNotificationStatus] = useState(() => {
    if (typeof Notification === 'undefined') return 'unsupported';
    return Notification.permission;
  });
  const [enabling, setEnabling] = useState(false);

  const handleEnableNotifications = async () => {
    setEnabling(true);
    try {
      const token = await requestNotificationPermissionAndToken();
      if (token) {
        setNotificationStatus('granted');
        showToast('Notifications enabled', 'You will now receive push notifications.', 'success');
      } else if (Notification.permission === 'denied') {
        setNotificationStatus('denied');
        showToast('Permission denied', 'Please enable notifications in your browser settings.', 'warning');
      } else {
        setNotificationStatus(Notification.permission);
      }
    } catch {
      setNotificationStatus('denied');
      showToast('Could not enable notifications', 'Push notifications are not supported in this browser.', 'error');
    } finally {
      setEnabling(false);
    }
  };

  const renderNotificationCard = () => {
    if (notificationStatus === 'granted') return null;

    if (notificationStatus === 'denied') {
      return (
        <div className="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-center justify-between">
          <div>
            <h3 className="text-sm font-semibold text-amber-900">Push notifications are blocked</h3>
            <p className="text-xs text-amber-700 mt-0.5">
              You previously dismissed the permission prompt. To re-enable notifications, click the lock icon in your browser's address bar and update the notification permission for this site.
            </p>
          </div>
          <button
            onClick={handleEnableNotifications}
            disabled={enabling}
            className="px-4 py-2 rounded-lg bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700 disabled:opacity-50 transition-colors"
          >
            {enabling ? 'Checking...' : 'Try Again'}
          </button>
        </div>
      );
    }

    if (notificationStatus === 'unsupported') {
      return (
        <div className="bg-slate-50 border border-slate-200 rounded-xl p-4">
          <h3 className="text-sm font-semibold text-slate-700">Push notifications unavailable</h3>
          <p className="text-xs text-slate-500 mt-0.5">Your browser or context doesn't support push notifications. Use HTTPS or localhost for the best experience.</p>
        </div>
      );
    }

    // 'default' — never asked yet
    return (
      <div className="bg-indigo-50 border border-indigo-200 rounded-xl p-4 flex items-center justify-between">
        <div>
          <h3 className="text-sm font-semibold text-indigo-900">Enable push notifications</h3>
          <p className="text-xs text-indigo-700 mt-0.5">Stay alerted on new events, refunds, and security events.</p>
        </div>
        <button
          onClick={handleEnableNotifications}
          disabled={enabling}
          className="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50 transition-colors"
        >
          {enabling ? 'Enabling...' : 'Enable'}
        </button>
      </div>
    );
  };

  if (error) {
    return (
      <div className="p-4 rounded-md bg-red-50 text-red-800">
        {error}
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Admin Dashboard</h1>
      </div>

      {renderNotificationCard()}

      <NavigationTiles />

      <QuickStatsSection loading={loading} data={quickStats} />

      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <MetricCard loading={loading} data={metrics} />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div className="lg:col-span-2">
          <ActivityFeed loading={loading} data={activity} />
        </div>
        <div>
          <AlertsSection loading={loading} data={alerts} />
        </div>
      </div>
    </div>
  );
};

export default AdminDashboardPage;
