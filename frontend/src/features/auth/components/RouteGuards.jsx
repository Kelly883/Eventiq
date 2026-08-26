import React, { useEffect } from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useAuthContext } from '../context/AuthContext';
import { showToast } from '../../../lib/api';
import { LoadingSpinner } from '../../common';

const getUserRole = (user) => {
  if (user?.roles?.some((r) => r.name === 'organizer')) return 'organizer';
  if (user?.roles?.some((r) => r.name === 'admin')) return 'admin';
  return null;
};

/**
 * Redirects while firing a toast once, from an effect — never during render
 * (keeps the guard pure under StrictMode double-rendering).
 */
const ToastRedirect = ({ to, state = undefined, title, description, type }) => {
  useEffect(() => {
    showToast(title, description, type, 5000);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return <Navigate to={to} replace state={state} />;
};

export const ProtectedRoute = ({ children, requiredRole = null, unauthenticatedToast = true }) => {
  const { user, loading, checkAdminAccess } = useAuthContext();
  const location = useLocation();

  if (loading) {
    return <LoadingSpinner message="Checking authentication..." />;
  }

  if (!user) {
    const toastTitle = unauthenticatedToast ? 'Sign in to view your tickets' : 'Session Expired';
    const toastDescription = unauthenticatedToast ? '' : 'Your session has expired. Please log in again.';
    return (
      <ToastRedirect
        to="/login"
        state={{ from: location }}
        title={toastTitle}
        description={toastDescription}
        type="warning"
      />
    );
  }

  if (requiredRole === 'admin' && !checkAdminAccess()) {
    return (
      <ToastRedirect
        to="/dashboard/user"
        state={{
          deniedByRole: 'admin',
          attemptedPath: location.pathname,
          message: 'Admin access is required for that page.',
          messageType: 'warning',
        }}
        title="Access Denied"
        description="Only admins can access this page"
        type="warning"
      />
    );
  }

  if (requiredRole === 'organizer' && !user?.roles?.some((r) => r.name === 'organizer')) {
    return (
      <ToastRedirect
        to="/dashboard/user"
        state={{
          deniedByRole: 'organizer',
          attemptedPath: location.pathname,
          message: 'That page is for organizers only. You need organizer privileges to manage events.',
          messageType: 'warning',
        }}
        title="Access Denied"
        description="Organizers only — 403"
        type="warning"
      />
    );
  }

  return children;
};

export const PublicRoute = ({ children }) => {
  const { user, loading } = useAuthContext();

  if (loading) {
    return <LoadingSpinner message="Checking authentication..." />;
  }

  if (user) {
    const role = getUserRole(user);
    if (role === 'organizer') {
      return <Navigate to="/dashboard/organizer" replace />;
    }
    if (role === 'admin') {
      return <Navigate to="/admin/roles" replace />;
    }
    return <Navigate to="/dashboard/user" replace />;
  }

  return children;
};
