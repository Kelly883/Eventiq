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

export const ProtectedRoute = ({ children, requiredRole = null, requiredRoles = null, unauthenticatedToast = true }) => {
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

  // Check multiple roles (OR logic - any one role grants access)
  if (requiredRoles && requiredRoles.length > 0) {
    const userRoles = user?.roles?.map((r) => r.name) || [];
    const hasRequiredRole = requiredRoles.some((role) => userRoles.includes(role));
    if (!hasRequiredRole) {
      const isCheckInRoute = location.pathname.startsWith('/check-in') || location.pathname.startsWith('/venue');
      if (isCheckInRoute) {
        return (
          <div className="min-h-[60vh] flex items-center justify-center p-6 md:p-10 bg-slate-50">
            <div className="max-w-md w-full bg-white rounded-2xl border border-slate-200 p-8 shadow-sm text-center">
              <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-amber-50 border border-amber-100 text-2xl">🔒</div>
              <h2 className="text-xl font-bold text-slate-900">Not Authorized</h2>
              <p className="mt-2 text-sm text-slate-500">You don&apos;t have permission to access check-in. VenueStaff or Organizer role required.</p>
              <div className="mt-6 flex justify-center gap-3">
                <a href="/" className="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">← Back to Home</a>
                <a href="/dashboard" className="px-4 py-2 rounded-lg border border-slate-200 bg-white text-sm">Go to Dashboard</a>
              </div>
            </div>
          </div>
        );
      }
      return (
        <ToastRedirect
          to="/dashboard"
          state={{
            deniedByRole: requiredRoles.join('|'),
            attemptedPath: location.pathname,
            message: `Access denied. Required roles: ${requiredRoles.join(' or ')}.`,
            messageType: 'warning',
          }}
          title="Access Denied"
          description={`You need one of these roles: ${requiredRoles.join(', ')}`}
          type="warning"
        />
      );
    }
  }

  if (requiredRole === 'admin' && !checkAdminAccess()) {
    return (
      <ToastRedirect
        to="/login"
        state={{ from: location }}
        title="Session expired"
        description="Your session has expired. Please log in again."
        type="warning"
      />
    );
  }

  if (requiredRole === 'organizer' && !user?.roles?.some((r) => r.name === 'organizer')) {
    return (
      <ToastRedirect
        to="/dashboard"
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
    const roles = user?.roles?.map((r) => r.name) || [];
    // Venue staff (venue_staff, organizer, admin) → dedicated staff dashboard
    if (roles.some((r) => ['venue_staff', 'organizer', 'admin'].includes(r))) {
      return <Navigate to="/venue/dashboard" replace />;
    }
    // Fallback for logged-in users without a dedicated dashboard
    const role = getUserRole(user);
    if (role === 'organizer') {
      return <Navigate to="/dashboard/organizer" replace />;
    }
    if (role === 'admin') {
      return <Navigate to="/admin/roles" replace />;
    }
    return <Navigate to="/dashboard" replace />;
  }

  return children;
};
