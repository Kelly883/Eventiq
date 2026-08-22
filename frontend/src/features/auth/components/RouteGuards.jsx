import React from 'react';
import { Navigate, useLocation, useNavigate } from 'react-router-dom';
import { useAuthContext } from '../context/AuthContext';
<<<<<<< HEAD
import { api } from '../../../lib/api';
=======
import { LoadingSpinner } from '../../common';
>>>>>>> origin/main

export const ProtectedRoute = ({ children, requiredRole = null }) => {
  const { user, loading, checkAdminAccess, sessionExpired, refreshAuth } = useAuthContext();
  const location = useLocation();
  const navigate = useNavigate();

  if (loading) {
    return <LoadingSpinner message="Checking authentication..." />;
  }

  if (!user) {
<<<<<<< HEAD
    api.showToast(
      'Session Expired',
      'Your session has expired. Please log in again.',
      'warning'
    );
=======
>>>>>>> origin/main
    return <Navigate to="/login" replace state={{ from: location }} />;
  }

  if (requiredRole === 'admin' && !checkAdminAccess()) {
<<<<<<< HEAD
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
=======
    return <Navigate to="/settings/permissions" replace state={{
      message: 'Access Denied — only admins can manage roles',
      from: location.pathname,
      messageType: 'error',
    }} />;
  }

  if (requiredRole === 'organizer' && !user?.roles?.some((r) => r.name === 'organizer')) {
    return <Navigate to="/access-denied" replace state={{
      message: 'Access Denied — organizers only',
      from: location.pathname,
    }} />;
>>>>>>> origin/main
  }

  return children;
};

export const PublicRoute = ({ children }) => {
  const { user, loading } = useAuthContext();

  if (loading) {
    return <LoadingSpinner message="Checking authentication..." />;
  }

  if (user) {
    return <Navigate to="/dashboard/organizer" replace />;
  }

  return children;
};
