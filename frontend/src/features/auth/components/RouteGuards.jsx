import React from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useAuthContext } from '../context/AuthContext';
import { api } from '../../../lib/api';
import { LoadingSpinner } from '../../common';

const getUserRole = (user) => {
  if (user?.roles?.some((r) => r.name === 'organizer')) return 'organizer';
  if (user?.roles?.some((r) => r.name === 'admin')) return 'admin';
  return null;
};

export const ProtectedRoute = ({ children, requiredRole = null }) => {
  const { user, loading, checkAdminAccess } = useAuthContext();
  const location = useLocation();

  if (loading) {
    return <LoadingSpinner message="Checking authentication..." />;
  }

  if (!user) {
    api.showToast(
      'Session Expired',
      'Your session has expired. Please log in again.',
      'warning'
    );
    return <Navigate to="/login" replace state={{ from: location }} />;
  }

  if (requiredRole === 'admin' && !checkAdminAccess()) {
    api.showToast(
      'Access Denied',
      'Only admins can manage roles',
      'warning',
      5000
    );
    return <Navigate to="/settings/permissions" replace />;
  }

  if (requiredRole === 'organizer' && !user?.roles?.some((r) => r.name === 'organizer')) {
    api.showToast(
      'Access Denied',
      'Organizers only',
      'warning',
      5000
    );
    return <Navigate to="/dashboard/user" replace />;
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
      return <Navigate to="/settings/permissions" replace />;
    }
    return <Navigate to="/dashboard/user" replace />;
  }

  return children;
};
