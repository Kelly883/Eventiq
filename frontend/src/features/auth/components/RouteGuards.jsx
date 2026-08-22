import React from 'react';
import { Navigate, useLocation, useNavigate } from 'react-router-dom';
import { useAuthContext } from '../context/AuthContext';
import { api } from '../../../lib/api';

const getUserRole = (): string | null => {
  const user = useAuthContext().user;
  return user?.roles?.some((r: { name: string }) => r.name === 'organizer')
    ? 'organizer'
    : user?.roles?.some((r: { name: string }) => r.name === 'admin')
      ? 'admin'
      : null;
};

export const ProtectedRoute = ({ children, requiredRole = null }) => {
  const { user, loading, checkAdminAccess, sessionExpired, refreshAuth, navigate } = useAuthContext();
  const location = useLocation();

  if (loading) {
    return <div>Loading...</div>;
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
    const role = getUserRole();
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
