import React from 'react';
import { useAdminDashboard } from '../hooks/useAdminDashboard';
import { MetricCard, ActivityFeed, AlertsSection, QuickStatsSection, NavigationTiles } from '../components/dashboard';

const AdminDashboardPage = () => {
  const { loading, error, metrics, activity, alerts, quickStats } = useAdminDashboard();

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

      <NavigationTiles />

      <QuickStatsSection loading={loading} data={quickStats} />

      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <MetricCard label="Total Revenue" value="$48,230" trend="up" trendLabel="12.5" loading={loading} />
        <MetricCard label="Total Events" value="24" trend="up" trendLabel="4.2" loading={loading} />
        <MetricCard label="Total Attendees" value="1,840" trend="down" trendLabel="3.1" loading={loading} />
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
