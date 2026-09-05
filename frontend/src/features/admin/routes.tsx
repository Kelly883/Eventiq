import React from 'react';
import { Route } from 'react-router-dom';
import AdminDashboardPage from './pages/AdminDashboardPage';
import AdminSettingsPage from './pages/AdminSettingsPage';
import AdminRoleManagementPage from '../roles/pages/AdminRoleManagementPage';
import FraudDetectionDashboardPage from '../fraud/pages/FraudDetectionDashboardPage';
import AdminDeliveryDashboardPage from '../ticket-delivery/pages/AdminDeliveryDashboardPage';
import AdminAnalyticsPage from '../analytics/pages/AdminAnalyticsPage';
import AdminSettlementDashboardPage from '../payouts/pages/AdminSettlementDashboardPage';
import AdminRefundDashboardPage from '../refunds/pages/AdminRefundDashboardPage';

const AdminRoutes = () => {
  const routes = [
    { path: '', element: <AdminDashboardPage /> },
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
