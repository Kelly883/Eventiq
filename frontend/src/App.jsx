import React, { useEffect, useState, Suspense, lazy, useRef } from 'react';
import { Routes, Route, NavLink, Navigate, useLocation, useNavigate, useParams } from 'react-router-dom';
import { LoadingSpinner, ErrorBoundary } from './features/common';
import BrandLogo from './features/common/components/BrandLogo';
import ToastContainer from './features/notifications/components/ToastContainer';
import { useFCMTokenSync } from './features/push-notifications/hooks/useFCMTokenSync';
import { ProtectedRoute, PublicRoute } from './features/auth/components/RouteGuards';
import { useAuthContext } from './features/auth/context/AuthContext';
import { safeRedirectPath, normalizeFromPath } from './features/auth/utils';
import { api, showToast } from './lib/api';
import './App.css';
import './features/homepage/homepage.css';
import Homepage from './features/homepage/Homepage';

const SalesAnalyticsDashboardPage = lazy(() => import('./features/analytics/pages/SalesAnalyticsDashboardPage'));
const DetailedAnalyticsPage = lazy(() => import('./features/analytics/pages/DetailedAnalyticsPage'));
const AnalyticsComparisonPage = lazy(() => import('./features/analytics/pages/AnalyticsComparisonPage'));
const OrganizerDashboardPage = lazy(() => import('./features/dashboard/pages').then(m => ({ default: m.OrganizerDashboardPage })));
const CheckInDashboardPage = lazy(() => import('./features/check-in').then(m => ({ default: m.CheckInDashboardPage })));
const CheckInSearchPage = lazy(() => import('./features/check-in/pages/CheckInSearchPage'));
const CheckInStatsPage = lazy(() => import('./features/check-in/pages/CheckInStatsPage'));
const CheckInExportPage = lazy(() => import('./features/check-in/pages/CheckInExportPage'));
const CheckInHistoryPage = lazy(() => import('./features/check-in/pages/CheckInHistoryPage'));
const VenueStaffEventsPage = lazy(() => import('./features/venue-staff/pages/VenueStaffEventsPage'));
const VenueStaffDashboardPage = lazy(() => import('./features/venue-staff/pages/VenueStaffDashboardPage'));
const EventBrowsePage = lazy(() => import('./features/events/pages/EventBrowsePage'));
const EventDetailPage = lazy(() => import('./features/events/pages/EventDetailPage'));
const CategoryBrowsePage = lazy(() => import('./features/events/pages/CategoryBrowsePage'));
const EventCalendarPage = lazy(() => import('./features/events-calendar/pages/EventCalendarPage'));
const TicketStatusPage = lazy(() => import('./features/ticket-delivery/pages/TicketStatusPage'));
const DeliverySettingsPage = lazy(() => import('./features/ticket-delivery/pages/DeliverySettingsPage'));
const DeliveryStatusPage = lazy(() => import('./features/ticket-delivery/pages').then(m => ({ default: m.DeliveryStatusPage })));
const AdminDashboardPage = lazy(() => import('./features/admin/pages/AdminDashboardPage'));
const AdminRoutes = lazy(() => import('./features/admin/routes'));
const UserPermissionsPage = lazy(() => import('./features/roles/pages/UserPermissionsPage'));
const LoginPage = lazy(() => import('./features/auth/pages').then(m => ({ default: m.LoginPage })));
const RegisterPage = lazy(() => import('./features/auth/pages').then(m => ({ default: m.RegisterPage })));
const ForgotPasswordPage = lazy(() => import('./features/auth/pages').then(m => ({ default: m.ForgotPasswordPage })));
const ResetPasswordPage = lazy(() => import('./features/auth/pages').then(m => ({ default: m.ResetPasswordPage })));
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
const CheckoutCallbackPage = lazy(() => import('./features/checkout/pages/CheckoutCallbackPage'));
const OrderConfirmationPage = lazy(() => import('./features/checkout/pages/OrderConfirmationPage'));
const UserTicketsDashboardPage = lazy(() => import('./features/tickets/pages/UserTicketsDashboardPage'));
const TicketDetailPage = lazy(() => import('./features/tickets/pages/TicketDetailPage'));
const UserDashboardPage = lazy(() => import('./features/dashboard/pages/UserDashboardPage'));
const TicketInventoryDashboardPage = lazy(() => import('./features/ticket-inventory/pages').then(m => ({ default: m.TicketInventoryDashboardPage })));
const TicketTierManagementPage = lazy(() => import('./features/ticketing/pages').then(m => ({ default: m.TicketTierManagementPage })));
const EventPricingConfigPage = lazy(() => import('./features/pricing/pages').then(m => ({ default: m.EventPricingConfigPage })));
const OrganizerEventLayout = lazy(() => import('./features/events/components/OrganizerEventLayout'));
const SettingsLayout = lazy(() => import('./features/settings/components/SettingsLayout'));
const MyTicketsLayout = lazy(() => import('./features/tickets/components/MyTicketsLayout'));
const AdminLayout = lazy(() => import('./features/admin/components/AdminLayout'));
const DashboardLayout = lazy(() => import('./features/dashboard/components/DashboardLayout'));
const AccessDeniedPage = lazy(() => import('./features/common').then(m => ({ default: m.AccessDeniedPage })));
const MyOrganizerProfilePage = lazy(() => import('./features/organizer-profile/pages/MyOrganizerProfilePage'));
const OrganizerPayoutDashboardPage = lazy(() => import('./features/payouts/pages/OrganizerPayoutDashboardPage'));
const AdminSettlementDashboardPage = lazy(() => import('./features/payouts/pages/AdminSettlementDashboardPage'));
const UserRefundRequestPage = lazy(() => import('./features/refunds/pages/UserRefundRequestPage'));
const UserRefundStatusPage = lazy(() => import('./features/refunds/pages/UserRefundStatusPage'));
const AdminRefundDashboardPage = lazy(() => import('./features/refunds/pages/AdminRefundDashboardPage'));
const AdminPushTemplateManagementPage = lazy(() => import('./features/push-notifications/components/AdminPushTemplateManagementPage'));
const TrustSafetyPage = lazy(() => import('./features/static-pages/pages/TrustSafetyPage'));
const AboutPage = lazy(() => import('./features/static-pages/pages/AboutPage'));
const ContactPage = lazy(() => import('./features/static-pages/pages/ContactPage'));
const HelpPage = lazy(() => import('./features/static-pages/pages/HelpPage'));
const RefundPolicyPage = lazy(() => import('./features/static-pages/pages/RefundPolicyPage'));
const TermsPage = lazy(() => import('./features/static-pages/pages/TermsPage'));

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

/**
 * /venue/check-in/:eventId was folded into the unified check-in desk.
 * Bookmarks and older links keep working by carrying the event into
 * /check-in?eventId=… (any extra query params survive too).
 */
const VenueCheckInRedirect = ({ eventId: eventIdParam = null }) => {
  const params = useParams();
  const location = useLocation();
  const eventId = eventIdParam ?? params.eventId;

  const query = new URLSearchParams(location.search);
  if (eventId) query.set('eventId', eventId);
  const qs = query.toString();

  return <Navigate to={`/check-in${qs ? `?${qs}` : ''}`} replace />;
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
    visible: (_isLoggedIn, roles) => hasAnyRole(roles, 'organizer'),
  },
  {
    to: '/check-in',
    label: '🎟️ Quick Check-In',
    visible: (_isLoggedIn, roles) => hasAnyRole(roles, 'venue_staff', 'organizer'),
  },
  {
    to: '/venue/dashboard',
    label: 'Venue Dashboard',
    visible: (_isLoggedIn, roles) => hasAnyRole(roles, 'venue_staff', 'organizer'),
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
    label: '🔍 Check Ticket',
    visible: (isLoggedIn) => isLoggedIn,
  },
  {
    to: '/settings',
    label: '⚙️ Settings',
    visible: (isLoggedIn) => isLoggedIn,
  },
  {
    to: '/admin',
    label: '⚡ Admin',
    visible: (_isLoggedIn, roles) => hasAnyRole(roles, 'admin'),
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
  {
    to: '/organizer/payouts',
    label: '💰 Payouts',
    visible: (_isLoggedIn, roles) => hasAnyRole(roles, 'organizer'),
  },
  {
    to: '/admin/analytics',
    label: '📊 Analytics',
    visible: (_isLoggedIn, roles) => hasAnyRole(roles, 'admin'),
  },
  {
    to: '/admin/settlements/dashboard',
    label: '💼 Settlements',
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
  const isHomepage = location.pathname === '/';

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

  // Listen for auth-driven redirects emitted by AuthContext (e.g. session
  // expiry / cross-tab logout). We navigate via the router (SPA) instead of a
  // hard reload so unauthenticated users never cascade into a reload loop.
  const currentLocationRef = useRef(location);
  useEffect(() => {
    currentLocationRef.current = location;
  }, [location]);
  useEffect(() => {
    const handleRedirectLogin = () => {
      const loc = currentLocationRef.current;
      if (loc.pathname === '/login') return;
      navigate('/login', { replace: true, state: { from: loc } });
    };
    window.addEventListener('eventiq:redirect-login', handleRedirectLogin);
    return () => window.removeEventListener('eventiq:redirect-login', handleRedirectLogin);
  }, [navigate]);

  const processedFromRef = useRef(null);

  useEffect(() => {
    if (!user || !location.state?.from) return;
    const fromPath = normalizeFromPath(location.state.from);

    if (processedFromRef.current === fromPath) return;
    processedFromRef.current = fromPath;

    const safePath = safeRedirectPath(fromPath, user, '/dashboard');
    if (safePath === location.pathname) return;
    navigate(safePath, { replace: true });
  }, [user, location.state?.from, location.pathname, navigate, processedFromRef]);

  // Track if recovery banner has been dismissed in this session
  const [recoveryBannerDismissed, setRecoveryBannerDismissed] = useState(false);

  // Show banner when deep-link recovery is active — not on the homepage itself
  const recoveryPath = normalizeFromPath(location.state?.from);
  const recoveryBanner =
    user && location.state?.from && location.pathname !== '/' && !recoveryBannerDismissed ? (
      <div
        key="recovery-banner"
        className="fixed top-0 left-0 right-0 z-50 bg-indigo-100 border-b border-indigo-200 p-4 text-indigo-800 shadow-sm animate-slide-in-down"
        role="alert"
      >
        <div className="max-w-7xl mx-auto text-center">
          <p className="text-sm font-medium">
            <span className="font-bold">Remembering where you wanted to go&hellip;</span>
            navigating back to {recoveryPath}…
          </p>
          <button
            onClick={() => setRecoveryBannerDismissed(true)}
            className="mt-2 text-indigo-600 underline cursor-pointer text-sm"
          >
            Don't show again
          </button>
        </div>
      </div>
    ) : null;

  return (
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
        {/* Navigation Bar — hidden on auth pages and homepage (homepage has its own Header) */}
        {!isAuthPage && !isHomepage && (
          <header className="sticky top-0 z-50 bg-white border-b border-slate-200/80 shadow-sm backdrop-blur-md bg-white/90">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
              <div className="flex h-16 items-center justify-between">
                {/* Logo */}
                <NavLink to="/" className="app-brand" aria-label="eventIQ home">
                  <BrandLogo />
                </NavLink>

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
                      navigate('/login', { replace: true });
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
          <ErrorBoundary>
            <Suspense fallback={<LoadingSpinner message="Loading page..." />}>
              <Routes>
              <Route path="/" element={<Homepage />} />
              <Route path="/events" element={<EventBrowsePage />} />
            <Route path="/events/calendar" element={<EventCalendarPage />} />
            <Route path="/cart" element={<CartPage />} />
            <Route path="/checkout" element={<ProtectedRoute><CheckoutPage /></ProtectedRoute>} />
            <Route path="/checkout/callback" element={<ProtectedRoute><CheckoutCallbackPage /></ProtectedRoute>} />
            <Route path="/order/:orderId/confirmation" element={<ProtectedRoute><OrderConfirmationPage /></ProtectedRoute>} />
            <Route path="/my-tickets" element={<ProtectedRoute unauthenticatedToast={true}><MyTicketsLayout /></ProtectedRoute>}>
              <Route index element={<UserTicketsDashboardPage />} />
              <Route path="status" element={<TicketStatusPage />} />
              <Route path=":ticketId" element={<TicketDetailPage />} />
              <Route path=":ticketId/status" element={<TicketStatusPage />} />
              <Route path=":ticketId/delivery" element={<DeliveryStatusPage />} />
              <Route path=":ticketId/refund-request" element={<UserRefundRequestPage />} />
              <Route path=":ticketId/refund-status" element={<UserRefundStatusPage />} />
            </Route>
             <Route path="/admin" element={<ProtectedRoute requiredRole="admin" unauthenticatedToast={false}><AdminLayout /></ProtectedRoute>}>
               <Route index element={<AdminDashboardPage />} />
               <AdminRoutes />
             </Route>
            <Route path="/settings" element={<ProtectedRoute><SettingsLayout /></ProtectedRoute>}>
              <Route index element={<DeliverySettingsPage />} />
              <Route path="permissions" element={<UserPermissionsPage />} />
              <Route path="delivery-preferences" element={<DeliverySettingsPage />} />
            </Route>
            <Route path="/events/category/:categoryId" element={<CategoryBrowsePage />} />
            <Route path="/events/:eventId" element={<EventDetailPage />} />
            <Route path="/analytics" element={<SalesAnalyticsDashboardPage />} />
            <Route path="/analytics/:eventId" element={<SalesAnalyticsDashboardPage />} />
            <Route path="/organizer/analytics/compare" element={<ProtectedRoute requiredRole="organizer"><AnalyticsComparisonPage /></ProtectedRoute>} />
            <Route path="/dashboard" element={<ProtectedRoute><DashboardLayout /></ProtectedRoute>}>
              <Route path="organizer" element={<OrganizerDashboardPage />} />
              <Route index element={<UserDashboardPage />} />
            </Route>
            <Route path="/organizer/payouts" element={<ProtectedRoute requiredRole="organizer"><OrganizerPayoutDashboardPage /></ProtectedRoute>} />
            <Route path="/organizer/events" element={<ProtectedRoute requiredRole="organizer"><OrganizerEventListPage /></ProtectedRoute>} />
            <Route path="/organizer/events/create" element={<ProtectedRoute requiredRole="organizer"><EventCreatePage /></ProtectedRoute>} />
            <Route path="/organizer/events/:eventId" element={<ProtectedRoute requiredRole="organizer"><OrganizerEventLayout /></ProtectedRoute>}>
              <Route index element={<OrganizerEventOverviewPage />} />
              <Route path="edit" element={<EventEditPage />} />
              <Route path="inventory" element={<TicketInventoryDashboardPage />} />
              <Route path="ticketing" element={<TicketTierManagementPage />} />
              <Route path="ticketing/tier/:tierId/edit" element={<TicketTierManagementPage />} />
              <Route path="pricing" element={<EventPricingConfigPage />} />
              <Route path="analytics" element={<SalesAnalyticsDashboardPage />} />
              <Route path="analytics/detailed" element={<DetailedAnalyticsPage />} />
            </Route>
            <Route path="/o/:organizerId" element={<OrganizerPublicProfilePage />} />
            <Route path="/organizer/:organizerId" element={<OrganizerProfileCompatRedirect />} />
            <Route path="/my/profile" element={<ProtectedRoute requiredRole="organizer"><MyOrganizerProfilePage /></ProtectedRoute>} />
            <Route path="/organizer/profile" element={<ProtectedRoute requiredRole="organizer"><OrganizerProfileNotSetUpPage /></ProtectedRoute>} />
            <Route path="/organizer/profile/edit" element={<ProtectedRoute requiredRole="organizer"><OrganizerProfileEditPage /></ProtectedRoute>} />
            <Route path="/organizer/profile/settings" element={<ProtectedRoute requiredRole="organizer"><OrganizerProfileSettingsPage /></ProtectedRoute>} />
            <Route path="/venue/events" element={<ProtectedRoute requiredRoles={['venue_staff', 'organizer']}><VenueStaffEventsPage /></ProtectedRoute>} />
            <Route path="/venue/dashboard" element={<ProtectedRoute requiredRoles={['venue_staff', 'organizer']}><VenueStaffDashboardPage /></ProtectedRoute>} />
            <Route path="/venue/check-in" element={<Navigate to="/check-in" replace />} />
            <Route path="/venue/check-in/:eventId" element={<VenueCheckInRedirect />} />
            <Route path="/check-in" element={<ProtectedRoute requiredRoles={['venue_staff', 'organizer']}><CheckInDashboardPage /></ProtectedRoute>} />
            <Route path="/check-in/search" element={<ProtectedRoute requiredRoles={['venue_staff', 'organizer']}><CheckInSearchPage /></ProtectedRoute>} />
            <Route path="/check-in/stats" element={<ProtectedRoute requiredRoles={['venue_staff', 'organizer']}><CheckInStatsPage /></ProtectedRoute>} />
            <Route path="/check-in/export" element={<ProtectedRoute requiredRoles={['venue_staff', 'organizer']}><CheckInExportPage /></ProtectedRoute>} />
            <Route path="/check-in/history" element={<ProtectedRoute requiredRoles={['venue_staff', 'organizer']}><CheckInHistoryPage /></ProtectedRoute>} />
            <Route path="/venue-scan" element={<Navigate to="/venue/events" replace />} />
            <Route path="/login" element={<PublicRoute><LoginPage /></PublicRoute>} />
            <Route path="/register" element={<PublicRoute><RegisterPage /></PublicRoute>} />
            <Route path="/forgot-password" element={<PublicRoute><ForgotPasswordPage /></PublicRoute>} />
            <Route path="/reset-password" element={<PublicRoute><ResetPasswordPage /></PublicRoute>} />
            <Route path="/access-denied" element={<AccessDeniedPage />} />
            <Route path="/trust" element={<TrustSafetyPage />} />
            <Route path="/about" element={<AboutPage />} />
            <Route path="/contact" element={<ContactPage />} />
            <Route path="/help" element={<HelpPage />} />
            <Route path="/refund-policy" element={<RefundPolicyPage />} />
            <Route path="/terms" element={<TermsPage />} />
            <Route path="*" element={<Navigate to="/" replace />} />
            </Routes>
            </Suspense>
          </ErrorBoundary>
        </main>
      </div>
  );
}


export default App;
