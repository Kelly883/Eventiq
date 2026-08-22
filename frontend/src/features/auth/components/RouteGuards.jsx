import React from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useAuthContext } from '../context/AuthContext';

export const ProtectedRoute = ({ children, requiredRole = null }) => {
  const { user, loading, checkAdminAccess } = useAuthContext();
  const location = useLocation();

  if (loading) {
    return <div>Loading...</div>;
  }

  if (!user) {
    return <Navigate to="/login" replace state={{ from: location, message: 'Session expired', messageType: 'warning' }} />;
  }

  if (requiredRole === 'admin' && !checkAdminAccess()) {
    return <Navigate to="/settings/permissions" replace state={{ message: 'Access Denied — only admins can manage roles', messageType: 'warning' }} />;
  }

  if (requiredRole === 'organizer' && !user?.roles?.some((r) => r.name === 'organizer')) {
    return <Navigate to="/dashboard/user" replace state={{ message: 'Access Denied — organizers only', messageType: 'warning' }} />;
  }

  return children;
};

export const PublicRoute = ({ children }) => {
  const { user, loading } = useAuthContext();

  if (loading) {
    return <div>Loading...</div>;
  }

  if (user) {
    return <Navigate to="/dashboard/organizer" replace />;
  }

  return children;
};
