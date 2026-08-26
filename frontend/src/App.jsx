import React, { useEffect, useState, Suspense, lazy } from 'react';
import { BrowserRouter, Routes, Route, NavLink, Navigate, useLocation, useNavigate, useParams } from 'react-router-dom';
import { LoadingSpinner } from './features/common';
import ToastContainer from './features/notifications/components/ToastContainer';
import { useFCMTokenSync } from './features/push-notifications/hooks/useFCMTokenSync';
import { ProtectedRoute, PublicRoute } from './features/auth/components/RouteGuards';
import { useAuthContext } from './features/auth/context/AuthContext';
import { api, showToast } from './lib/api';
import './App.css';

const SalesAnalyticsDashboardPage = lazy(() => import('./features/analytics/pages/SalesAnalyticsDashboardPage'));
const DetailedAnalyticsPage = lazy(() => import('./features/analytics/pages/DetailedAnalyticsPage'));
const AnalyticsComparisonPage = lazy(() => import('./features/analytics/pages/AnalyticsComparisonPage'));
const OrganizerDashboardPage = lazy(() => import('./features/dashboard/pages').then(m => ({ default: m.OrganizerDashboardPage })));
const CheckInDashboardPage = lazy(() => import('./features/check-in').then(m => ({ default: m.CheckInDashboardPage })));
const VenueCheckInPage = lazy(() => import('./features/qr-code-ticketing/pages/VenueCheckInPage'));
const EventBrowsePage = lazy(() => import('./features/events/pages/EventBrowsePage'));
const EventDetailPage = lazy(() => import('./features/events/pages/EventDetailPage'));
const CategoryBrowsePage = lazy(() => import('./features/events/pages/CategoryBrowsePage'));
const EventCalendarPage = lazy(() => import('./features/events-calendar/pages/EventCalendarPage'));
const TicketStatusPage = lazy(() => import('./features/ticket-delivery/pages/TicketStatusPage'));
const DeliverySettingsPage = lazy(() => import('./features/ticket-delivery/pages/DeliverySettingsPage'));
const AdminDeliveryDashboardPage = lazy(() => import('./features/ticket-delivery/pages/AdminDeliveryDashboardPage'));
const AdminEmailTemplateManagementPage = lazy(() => import('./features/email-notifications/pages/AdminEmailTemplateManagementPage'));
const AdminRoleManagementPage = lazy(() => import('./features/roles/pages/AdminRoleManagementPage'));
const UserPermissionsPage = lazy(() => import('./features/roles/pages/UserPermissionsPage'));
const OrganizerPublicProfilePage = lazy(() => import('./features/organizer-profile/pages/OrganizerPublicProfilePage'));
const OrganizerProfileEditPage = lazy(() => import('./features/organizer-profile/pages/OrganizerProfileEditPage'));
const OrganizerProfileSettingsPage = lazy(() => import('./features/organizer-profile/pages/OrganizerProfileSettingsPage'));
const OrganizerProfileView = lazy(() => import('./features/organizer-profile/components/OrganizerProfileView'));
const OrganizerEventListPage = lazy(() => import('./features/events/pages/OrganizerEventListPage'));
const EventCreatePage = lazy(() => import('./features/events/pages/EventCreatePage'));
const EventEditPage = lazy(() => import('./features/events/pages/EventEditPage'));
const OrganizerEventOverviewPage = lazy(() => import('./features/events/pages/OrganizerEventOverviewPage'));
const CartPage = lazy(() => import('./features/checkout/pages/CartPage'));
const CheckoutPage = lazy(() => import('./features/checkout/pages/CheckoutPage'));
const OrderConfirmationPage = lazy(() => import('./features/checkout/pages/OrderConfirmationPage'));
const UserTicketsDashboardPage = lazy(() => import('./features/tickets/pages/UserTicketsDashboardPage'));
const TicketDetailPage = lazy(() => import('./features/tickets/pages/TicketDetailPage'));
const UserDashboardPage = lazy(() => import('./features/dashboard/pages/UserDashboardPage'));
const FraudDetectionDashboardPage = lazy(() => import('./features/fraud/pages/FraudDetectionDashboardPage'));
const LoginPage = lazy(() => import('./features/auth/pages').then(m => ({ default: m.LoginPage })));
const RegisterPage = lazy(() => import('./features/auth/pages').then(m => ({ default: m.RegisterPage })));
const ForgotPasswordPage = lazy(() => import('./features/auth/pages').then(m => ({ default: m.ForgotPasswordPage })));
const ResetPasswordPage = lazy(() => import('./features/auth/pages').then(m => ({ default: m.ResetPasswordPage })));
const TicketInventoryDashboardPage = lazy(() => import('./features/ticket-inventory/pages').then(m => ({ default: m.TicketInventoryDashboardPage })));
const TicketTierManagementPage = lazy(() => import('./features/ticketing/pages').then(m => ({ default: m.TicketTierManagementPage })));
const EventPricingConfigPage = lazy(() => import('./features/pricing/pages').then(m => ({ default: m.EventPricingConfigPage })));
const AccessDeniedPage = lazy(() => import('./features/common').then(m => ({ default: m.AccessDeniedPage })));
const MyOrganizerProfilePage = lazy(() => import('./features/organizer-profile/pages/MyOrganizerProfilePage'));

const AUTH_PAGES = ['/login', '/register', '/forgot-password', '/reset-password', '/access-denied'];

/**
 * renders the "profile not set up yet" state for the logged-in organizer
   when /organizer/profile is accessed without a dynamic segment.
   Falls back to /my/profile if no organizer session.
 */
const OrganizerProfileNotSetUpPage = () => {
  const { organizerId } = useAuthContext();

  return organizerId ? (
    <OrganizerProfileView organizerId={organizerId} />
  ) : (
    <Navigate to="/my/profile" replace />
  );
};

/**
 * Backward compatibility: public profiles moved from /organizer/:id to
 * /o/:id. Previously shared links keep working; query strings survive.
 */
const OrganizerProfileCompatRedirect = () => {
  const { organizerId } = useParams();
  const location = useLocation();

  return <Navigate to={`/o/${organizerId}${location.search}`} replace />;
};

const hasAnyRole = (roles, ...names) => names.some((n) => roles.includes(n));

// Visibility rules keep the nav honest: users only see destinations they can
// actually reach. Public routes stay visible to everyone.
const NAV_ITEMS = [
  { to: '/analytics', label: '📈 Analytics', visible: () => true },
  {
    to: '/events',
    label: '📋 Browse Events',
    visible: () => true,
  },
  {
    to: '/events/calendar',
    label: '🗓️ Calendar',
    visible: () => true,
  },
  {
    to: '/cart',
    label: '🛒 Cart',
    visible: () => true,
  },
  {
    to: '/dashboard/organizer',
    label: '💼 Organizer',
    visible: (isLoggedIn) => isLoggedIn,
  },
  {
    to: '/check-in',
    label: '🎟️ Check-In Desk',
    visible: (_isLoggedIn, roles) => hasAnyRole(roles, 'organizer', 'admin'),
  },
  {
    to: '/venue-scan',
    label: '📷 Gate Scanner',
    visible: (_isLoggedIn, roles) => hasAnyRole(roles, 'organizer', 'admin'),
  },
  {
    to: '/organizer/events',
    label: '📦 Events',
    visible: (_isLoggedIn, roles) => hasAnyRole(roles, 'organizer'),
  },
  {
    to: '/dashboard',
    label: 'Dashboard',
    visible: (isLoggedIn, roles) => isLoggedIn && hasAnyRole(roles, 'organizer'),
  },
  {
    to: '/my/profile',
    label: '👤 Profile',
    visible: (_isLoggedIn, roles) => hasAnyRole(roles, 'organizer'),
  },
  {
    to: '/my-tickets',
    label: '🎫 My Tickets',
    visible: (isLoggedIn) => isLoggedIn,
  },
  {
    to: '/my-tickets/status',
    label: '🔍 Ticket Status',
    visible: (isLoggedIn) => isLoggedIn,
  },
  {
    to: '/settings/delivery-preferences',
    label: '⚙️ Delivery Settings',
    visible: (isLoggedIn) => isLoggedIn,
  },
  {
    to: '/admin/roles',
    label: '🛡️ Admin Roles',
    visible: (_isLoggedIn, roles) => hasAnyRole(roles, 'admin'),
  },
  {
    to: '/admin/fraud/dashboard',
    label: '🕵️ Fraud Detection',
    visible: (_isLoggedIn, roles) => hasAnyRole(roles, 'admin'),
  },
];

function App() {
  useFCMTokenSync();
  const location = useLocation();
  const navigate = useNavigate();
  const { user, logout, sessionExpired, refreshAuth } = useAuthContext();
  const isLoggedIn = Boolean(user);
  const roles = user?.roles?.map((r) => r.name) || [];
  const isAuthPage = AUTH_PAGES.some((path) => location.pathname === path);

  // Session warning toast: show at 55s before auto-expire (60s interval)
  const [sessionWarningShown, setSessionWarningShown] = useState(false);
  useEffect(() => {
    if (user && !sessionWarningShown) {
      const timeout = setTimeout(() => {
        setSessionWarningShown(true);
        showToast(
          'Session Expiring',
          'Your session will expire soon. Save your work or continue activity to stay logged in.',
          'warning',
          8000
        );
      }, 55000);
      return () => clearTimeout(timeout);
    }
  }, [user, sessionWarningShown]);

  useEffect(() => {
    if (user && location.state?.from) {
      const getRedirectPath = (to) => {
        const userRoles = user?.roles?.map((r) => r.name) || [];
        if (to === '/dashboard/organizer' && !userRoles.includes('organizer')) {
          return '/dashboard/user';
        }
        if (to.startsWith('/admin/') && !userRoles.includes('admin')) {
          return '/dashboard/user';
        }
        if (to.startsWith('/organizer/') && !userRoles.includes('organizer')) {
          return '/dashboard/user';
        }
        return to;
      };

      const safePath = getRedirectPath(location.state.from.pathname);
      navigate(safePath, { replace: true });
    }
  }, [user, location.state?.from, navigate]);

  // Show banner when deep-link recovery is active
  const recoveryBanner = user && location.state?.from ? (
    <div
      key="recovery-banner"
      className="fixed top-0 left-0 right-0 z-50 bg-indigo-100 border-b border-indigo-200 p-4 text-indigo-800 shadow-sm animate-slide-in-down"
      role="alert"
    >
      <div className="max-w-7xl mx-auto text-center">
        <p className="text-sm font-medium">
          <span className="font-bold">Remembering where you wanted to go&hellip;</span>
          navigating back to {location.state.from.replace('/', '/')}…
        </p>
      </div>
    </div>
  ) : null;

  return (
    <BrowserRouter>
      <div className="flex flex-col min-h-screen bg-slate-50 font-sans">
        <noscript>
          <div className="fixed top-0 left-0 right-0 z-50 bg-white border-b border-slate-200/80 p-4 text-slate-900 shadow-sm">
            <div className="max-w-2xl mx-auto text-center">
              <h2 className="text-2xl font-bold text-red-600">JavaScript Required</h2>
              <p className="text-slate-700 mt-2">Eventiq requires JavaScript to function properly. Please enable JavaScript in your browser settings to access all features including admin route protection, session management, and interactive elements.</p>
              <p className="text-slate-600 mt-4 text-sm">If JavaScript is disabled, the server-side authentication middleware will still protect admin routes, but the interactive user interface will not be available.</p>
            </div>
          </div>
        </noscript>
        <ToastContainer />
        {recoveryBanner}
        {/* Navigation Bar — hidden on auth pages */}
        {!isAuthPage && (
          <header className="sticky top-0 z-50 bg-white border-b border-slate-200/80 shadow-sm backdrop-blur-md bg-white/90">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
              <div className="flex h-16 items-center justify-between">
                {/* Logo */}
                <div className="flex items-center gap-2">
                  <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600 text-white font-black text-lg shadow-md shadow-indigo-200">
                    E
                  </div>
                  <span className="text-xl font-black text-slate-900 tracking-tight">Eventiq</span>
                  <span className="hidden sm:inline-block text-[10px] font-bold uppercase tracking-wider text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full border border-indigo-100">
                    v1.0
                  </span>
                </div>

                {/* Navigation Links — filtered by auth state and role so users
                    never see destinations they cannot access */}
                <nav className="flex space-x-1 sm:space-x-3">
                  {NAV_ITEMS.map((item) => {
                    if (!item.visible(isLoggedIn, roles)) return null;
                    return (
                      <NavLink
                        key={item.to}
                        to={item.to}
                        className={({ isActive }) =>
                          `px-3.5 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all flex items-center gap-1.5 ${
                            isActive
                              ? 'bg-indigo-50 text-indigo-600 shadow-sm shadow-indigo-100/40 border border-indigo-100/50'
                              : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'
                          }`
                        }
                      >
                        {item.label}
                      </NavLink>
                    );
                  })}
                </nav>

                {/* Auth buttons */}
                {user ? (
                  <button
                    type="button"
                    onClick={() => {
                      logout();
                      window.location.href = '/login';
                    }}
                    className="px-3 py-1.5 rounded-lg text-xs sm:text-sm font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 transition-colors"
                  >
                    🔓 Logout
                  </button>
                ) : (
                  <NavLink
                    to="/login"
                    className={({ isActive }) =>
                      `px-3.5 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all flex items-center gap-1.5 ${
                        isActive
                          ? 'bg-indigo-50 text-indigo-600 shadow-sm shadow-indigo-100/40 border border-indigo-100/50'
                          : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'
                      }`
                    }
                  >
                    🔒 Sign In
                  </NavLink>
                )}
              </div>
            </div>
          </header>
        )}

        {/* Page Content */}
        <main className="flex-1">
          <Suspense fallback={<LoadingSpinner message="Loading page..." />}>
            <Routes>
            <Route path="/" element={<Navigate to="/analytics" replace />} />
            <Route path="/events" element={<EventBrowsePage />} />
            <Route path="/events/calendar" element={<EventCalendarPage />} />
            <Route path="/cart" element={<CartPage />} />
            <Route path="/checkout" element={<ProtectedRoute><CheckoutPage /></ProtectedRoute>} />
            <Route path="/order/:orderId/confirmation" element={<ProtectedRoute><OrderConfirmationPage /></ProtectedRoute>} />
            <Route path="/my-tickets" element={<ProtectedRoute unauthenticatedToast={true}><UserTicketsDashboardPage /></ProtectedRoute>} />
            <Route path="/my-tickets/:ticketId" element={<ProtectedRoute unauthenticatedToast={true}><TicketDetailPage /></ProtectedRoute>} />
            <Route path="/dashboard" element={<ProtectedRoute unauthenticatedToast={true}><UserDashboardPage /></ProtectedRoute>} />
            <Route path="/my-tickets/:ticketId/status" element={<ProtectedRoute><TicketStatusPage /></ProtectedRoute>} />
            <Route path="/my-tickets/status" element={<ProtectedRoute><TicketStatusPage /></ProtectedRoute>} />
            <Route path="/admin/email-templates" element={<ProtectedRoute requiredRole="admin"><AdminEmailTemplateManagementPage /></ProtectedRoute>} />
            <Route path="/admin/roles" element={<ProtectedRoute requiredRole="admin"><AdminRoleManagementPage /></ProtectedRoute>} />
            <Route path="/admin/fraud/dashboard" element={<ProtectedRoute requiredRole="admin"><FraudDetectionDashboardPage /></ProtectedRoute>} />
            <Route path="/admin/delivery/dashboard" element={<ProtectedRoute requiredRole="admin"><AdminDeliveryDashboardPage /></ProtectedRoute>} />
            <Route path="/settings/permissions" element={<ProtectedRoute><UserPermissionsPage /></ProtectedRoute>} />
            <Route path="/settings/delivery-preferences" element={<ProtectedRoute><DeliverySettingsPage /></ProtectedRoute>} />
            <Route path="/events/category/:categoryId" element={<CategoryBrowsePage />} />
            <Route path="/events/:eventId" element={<EventDetailPage />} />
            <Route path="/analytics" element={<SalesAnalyticsDashboardPage />} />
            <Route path="/analytics/:eventId" element={<SalesAnalyticsDashboardPage />} />
            <Route path="/organizer/events/:eventId/analytics" element={<ProtectedRoute requiredRole="organizer"><SalesAnalyticsDashboardPage /></ProtectedRoute>} />
            <Route path="/organizer/events/:eventId/analytics/detailed" element={<ProtectedRoute requiredRole="organizer"><DetailedAnalyticsPage /></ProtectedRoute>} />
            <Route path="/organizer/analytics/compare" element={<ProtectedRoute requiredRole="organizer"><AnalyticsComparisonPage /></ProtectedRoute>} />
            <Route path="/dashboard/organizer" element={<ProtectedRoute requiredRole="organizer"><OrganizerDashboardPage /></ProtectedRoute>} />
            <Route path="/dashboard/user" element={<ProtectedRoute><UserDashboardPage /></ProtectedRoute>} />
            <Route path="/organizer/events" element={<ProtectedRoute requiredRole="organizer"><OrganizerEventListPage /></ProtectedRoute>} />
            <Route path="/organizer/events/create" element={<ProtectedRoute requiredRole="organizer"><EventCreatePage /></ProtectedRoute>} />
            <Route path="/organizer/events/:eventId" element={<ProtectedRoute requiredRole="organizer"><OrganizerEventOverviewPage /></ProtectedRoute>} />
            <Route path="/organizer/events/:eventId/edit" element={<ProtectedRoute requiredRole="organizer"><EventEditPage /></ProtectedRoute>} />
            <Route path="/organizer/events/:eventId/inventory" element={<ProtectedRoute requiredRole="organizer"><TicketInventoryDashboardPage /></ProtectedRoute>} />
            <Route path="/organizer/events/:eventId/ticketing" element={<ProtectedRoute requiredRole="organizer"><TicketTierManagementPage /></ProtectedRoute>} />
            <Route path="/organizer/events/:eventId/ticketing/tier/:tierId/edit" element={<ProtectedRoute requiredRole="organizer"><TicketTierManagementPage /></ProtectedRoute>} />
            <Route path="/organizer/events/:eventId/pricing" element={<ProtectedRoute requiredRole="organizer"><EventPricingConfigPage /></ProtectedRoute>} />
            <Route path="/o/:organizerId" element={<OrganizerPublicProfilePage />} />
            <Route path="/organizer/:organizerId" element={<OrganizerProfileCompatRedirect />} />
            <Route path="/my/profile" element={<ProtectedRoute requiredRole="organizer"><MyOrganizerProfilePage /></ProtectedRoute>} />
            <Route path="/organizer/profile" element={<ProtectedRoute requiredRole="organizer"><OrganizerProfileNotSetUpPage /></ProtectedRoute>} />
            <Route path="/organizer/profile/edit" element={<ProtectedRoute requiredRole="organizer"><OrganizerProfileEditPage /></ProtectedRoute>} />
            <Route path="/organizer/profile/settings" element={<ProtectedRoute requiredRole="organizer"><OrganizerProfileSettingsPage /></ProtectedRoute>} />
            <Route path="/check-in" element={<CheckInDashboardPage />} />
            <Route path="/venue-scan" element={<VenueCheckInPage />} />
            <Route path="/login" element={<PublicRoute><LoginPage /></PublicRoute>} />
            <Route path="/register" element={<PublicRoute><RegisterPage /></PublicRoute>} />
            <Route path="/forgot-password" element={<PublicRoute><ForgotPasswordPage /></PublicRoute>} />
            <Route path="/reset-password" element={<PublicRoute><ResetPasswordPage /></PublicRoute>} />
            <Route path="/access-denied" element={<AccessDeniedPage />} />
            <Route path="*" element={<Navigate to="/analytics" replace />} />
          </Routes>
          </Suspense>
        </main>
      </div>
    </BrowserRouter>
  );
}


export default App;
