import React from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useAuthContext } from '../context/AuthContext';
import { LoadingSpinner } from '../../common';

export const ProtectedRoute = ({ children, requiredRole = null }) => {
  const { user, loading, checkAdminAccess } = useAuthContext();
  const location = useLocation();

  if (loading) {
    return <LoadingSpinner message="Checking authentication..." />;
  }

  if (!user) {
    return <Navigate to="/login" replace state={{ from: location }} />;
  }

  if (requiredRole === 'admin' && !checkAdminAccess()) {
    return <Navigate to="/settings/permissions" replace state={{
      message: 'Access Denied — only admins can manage roles',
      from: location.pathname,
      messageType: 'warning',
    }} />;
  }

  if (requiredRole === 'organizer' && !user?.roles?.some((r) => r.name === 'organizer')) {
    return <Navigate to="/access-denied" replace state={{
      message: 'Access Denied — organizers only',
      from: location.pathname,
    }} />;
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
