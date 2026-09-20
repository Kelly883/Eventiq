import React, { useState } from 'react';
import { useAdminDashboard } from '../hooks/useAdminDashboard';
import { MetricCard, ActivityFeed, AlertsSection, QuickStatsSection, NavigationTiles } from '../components/dashboard';
import { requestNotificationPermissionAndToken } from '../../../config/firebase';
import { showToast } from '../../../lib/api';
import '../../../styles/shared-pages.css';

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
        <div className="spa-alert spa-alert--warning" style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
          <div>
            <h3 style={{ fontSize: '14px', fontWeight: 600, margin: 0 }}>Push notifications are blocked</h3>
            <p style={{ fontSize: '12px', margin: '4px 0 0' }}>
              You previously dismissed the permission prompt. To re-enable notifications, click the lock icon in your browser's address bar and update the notification permission for this site.
            </p>
          </div>
          <button
            onClick={handleEnableNotifications}
            disabled={enabling}
            className="spa-btn spa-btn--primary"
          >
            {enabling ? 'Checking...' : 'Try Again'}
          </button>
        </div>
      );
    }

    if (notificationStatus === 'unsupported') {
      return (
        <div className="spa-alert spa-alert--info">
          <h3 style={{ fontSize: '14px', fontWeight: 600, margin: 0 }}>Push notifications unavailable</h3>
          <p style={{ fontSize: '12px', margin: '4px 0 0' }}>Your browser or context doesn't support push notifications. Use HTTPS or localhost for the best experience.</p>
        </div>
      );
    }

    // 'default' — never asked yet
    return (
      <div className="spa-alert spa-alert--info" style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
        <div>
          <h3 style={{ fontSize: '14px', fontWeight: 600, margin: 0 }}>Enable push notifications</h3>
          <p style={{ fontSize: '12px', margin: '4px 0 0' }}>Stay alerted on new events, refunds, and security events.</p>
        </div>
        <button
          onClick={handleEnableNotifications}
          disabled={enabling}
          className="spa-btn spa-btn--primary"
        >
          {enabling ? 'Enabling...' : 'Enable'}
        </button>
      </div>
    );
  };

  if (error) {
    return (
      <div className="spa-alert spa-alert--error">
        {error}
      </div>
    );
  }

  return (
    <div className="spa-page">
      <div className="spa-container">
        <div className="spa-page__header">
          <h1 className="spa-page__title">Admin Dashboard</h1>
        </div>

        {renderNotificationCard()}

        <NavigationTiles />

        <QuickStatsSection loading={loading} data={quickStats} />

        <div className="spa-grid spa-grid--3">
          <MetricCard loading={loading} data={metrics} />
        </div>

        <div className="spa-grid spa-grid--3">
          <div style={{ gridColumn: 'span 2' }}>
            <ActivityFeed loading={loading} data={activity} />
          </div>
          <div>
            <AlertsSection loading={loading} data={alerts} />
          </div>
        </div>
      </div>
    </div>
  );
};

export default AdminDashboardPage;
