import React from 'react';
import { Route } from 'react-router-dom';
// @ts-ignore
import AdminDashboardPage from './pages/AdminDashboardPage';
// @ts-ignore
import AdminSettingsPage from './pages/AdminSettingsPage';
// @ts-ignore
import AdminUserManagementPage from './pages/AdminUserManagementPage';
// @ts-ignore
import AdminEventModerationPage from './pages/AdminEventModerationPage';
// @ts-ignore
import AdminRoleManagementPage from '../roles/pages/AdminRoleManagementPage';
// @ts-ignore
import FraudDetectionDashboardPage from '../fraud/pages/FraudDetectionDashboardPage';
// @ts-ignore
import AdminDeliveryDashboardPage from '../ticket-delivery/pages/AdminDeliveryDashboardPage';
// @ts-ignore
import AdminAnalyticsPage from '../analytics/pages/AdminAnalyticsPage';
// @ts-ignore
import AdminSettlementDashboardPage from '../payouts/pages/AdminSettlementDashboardPage';
// @ts-ignore
import AdminRefundDashboardPage from '../refunds/pages/AdminRefundDashboardPage';

const AdminRoutes = () => {
  const routes = [
    { path: '', element: <AdminDashboardPage /> },
    { path: 'users', element: <AdminUserManagementPage /> },
    { path: 'events', element: <AdminEventModerationPage /> },
    { path: 'roles', element: <AdminRoleManagementPage /> },
    { path: 'fraud/dashboard', element: <FraudDetectionDashboardPage /> },
    { path: 'delivery/dashboard', element: <AdminDeliveryDashboardPage /> },
    { path: 'analytics', element: <AdminAnalyticsPage /> },
    { path: 'settings', element: <AdminSettingsPage /> },
    { path: 'settlements/dashboard', element: <AdminSettlementDashboardPage /> },
    { path: 'refunds', element: <AdminRefundDashboardPage /> },
    { path: 'refunds/dashboard', element: <AdminRefundDashboardPage /> },
  ];

  return routes.map((route) => (
    <Route key={route.path} path={route.path} element={route.element} />
  ));
};

export default AdminRoutes;
