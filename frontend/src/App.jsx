import React from 'react';
import { BrowserRouter, Routes, Route, NavLink, Navigate, useLocation } from 'react-router-dom';
import SalesAnalyticsDashboardPage from './features/analytics/pages/SalesAnalyticsDashboardPage';
import { OrganizerDashboardPage, UserDashboardPage } from './features/dashboard/pages';
import { CheckInDashboardPage } from './features/check-in';
import VenueCheckInPage from './features/qr-code-ticketing/pages/VenueCheckInPage';
import EventBrowsePage from './features/events/pages/EventBrowsePage';
import EventDetailPage from './features/events/pages/EventDetailPage';
import CategoryBrowsePage from './features/events/pages/CategoryBrowsePage';
import EventCalendarPage from './features/events-calendar/pages/EventCalendarPage';
import TicketStatusPage from './features/ticket-delivery/pages/TicketStatusPage';
import AdminEmailTemplateManagementPage from './features/email-notifications/pages/AdminEmailTemplateManagementPage';
import AdminRoleManagementPage from './features/roles/pages/AdminRoleManagementPage';
import UserPermissionsPage from './features/roles/pages/UserPermissionsPage';
import OrganizerPublicProfilePage from './features/organizer-profile/pages/OrganizerPublicProfilePage';
import OrganizerProfileEditPage from './features/organizer-profile/pages/OrganizerProfileEditPage';
import OrganizerProfileSettingsPage from './features/organizer-profile/pages/OrganizerProfileSettingsPage';
import OrganizerEventListPage from './features/events/pages/OrganizerEventListPage';
import EventCreatePage from './features/events/pages/EventCreatePage';
import EventEditPage from './features/events/pages/EventEditPage';
import ToastContainer from './features/notifications/components/ToastContainer';
import { useFCMTokenSync } from './features/push-notifications/hooks/useFCMTokenSync';
import { ProtectedRoute, PublicRoute } from './features/auth/components/RouteGuards';
import { useAuthContext } from './features/auth/context/AuthContext';
import {
  LoginPage,
  RegisterPage,
  ForgotPasswordPage,
  ResetPasswordPage,
} from './features/auth/pages';
import { TicketInventoryDashboardPage, AdjustInventoryModal } from './features/ticket-inventory/pages';
import { TicketTierManagementPage } from './features/ticketing/pages';
import { EventPricingConfigPage, PricingPreviewModal } from './features/pricing/pages';
import { AccessDeniedPage } from './features/common';
import './App.css';

const AUTH_PAGES = ['/login', '/register', '/forgot-password', '/reset-password', '/access-denied'];

function App() {
  useFCMTokenSync();
  const location = useLocation();
  const { user, logout } = useAuthContext();
  const isAuthPage = AUTH_PAGES.some((path) => location.pathname === path);

  return (
    <BrowserRouter>
      <div className="flex flex-col min-h-screen bg-slate-50 font-sans">
        <ToastContainer />
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

                {/* Navigation Links */}
                <nav className="flex space-x-1 sm:space-x-3">
                  <NavLink
                    to="/analytics"
                    className={({ isActive }) =>
                      `px-3.5 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all flex items-center gap-1.5 ${
                        isActive
                          ? 'bg-indigo-50 text-indigo-600 shadow-sm shadow-indigo-100/40 border border-indigo-100/50'
                          : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'
                      }`
                    }
                  >
                    📈 Analytics
                  </NavLink>
                  <NavLink
                    to="/dashboard/organizer"
                    className={({ isActive }) =>
                      `px-3.5 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all flex items-center gap-1.5 ${
                        isActive
                          ? 'bg-indigo-50 text-indigo-600 shadow-sm shadow-indigo-100/40 border border-indigo-100/50'
                          : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'
                      }`
                    }
                  >
                    💼 Organizer
                  </NavLink>
                  <NavLink
                    to="/check-in"
                    className={({ isActive }) =>
                      `px-3.5 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all flex items-center gap-1.5 ${
                        isActive
                          ? 'bg-indigo-50 text-indigo-600 shadow-sm shadow-indigo-100/40 border border-indigo-100/50'
                          : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'
                      }`
                    }
                  >
                    🎟️ Check-In Desk
                  </NavLink>
                  <NavLink
                    to="/venue-scan"
                    className={({ isActive }) =>
                      `px-3.5 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all flex items-center gap-1.5 ${
                        isActive
                          ? 'bg-indigo-50 text-indigo-600 shadow-sm shadow-indigo-100/40 border border-indigo-100/50'
                          : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'
                      }`
                    }
                  >
                    📷 Gate Scanner
                  </NavLink>
                  <NavLink
                    to="/organizer/events"
                    className={({ isActive }) =>
                      `px-3.5 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all flex items-center gap-1.5 ${
                        isActive
                          ? 'bg-indigo-50 text-indigo-600 shadow-sm shadow-indigo-100/40 border border-indigo-100/50'
                          : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'
                      }`
                    }
                  >
                    📦 Events
                  </NavLink>
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
          <Routes>
            <Route path="/" element={<Navigate to="/analytics" replace />} />
            <Route path="/events" element={<EventBrowsePage />} />
            <Route path="/events/calendar" element={<EventCalendarPage />} />
            <Route path="/tickets/:ticketId/status" element={<TicketStatusPage />} />
            <Route path="/admin/email-templates" element={<AdminEmailTemplateManagementPage />} />
            <Route path="/admin/roles" element={<ProtectedRoute requiredRole="admin"><AdminRoleManagementPage /></ProtectedRoute>} />
            <Route path="/settings/permissions" element={<ProtectedRoute><UserPermissionsPage /></ProtectedRoute>} />
            <Route path="/events/category/:categoryId" element={<CategoryBrowsePage />} />
            <Route path="/events/:eventId" element={<EventDetailPage />} />
            <Route path="/analytics" element={<SalesAnalyticsDashboardPage />} />
            <Route path="/analytics/:eventId" element={<SalesAnalyticsDashboardPage />} />
            <Route path="/dashboard/organizer" element={<ProtectedRoute><OrganizerDashboardPage /></ProtectedRoute>} />
            <Route path="/dashboard/user" element={<ProtectedRoute><UserDashboardPage /></ProtectedRoute>} />
            <Route path="/dashboard" element={<Navigate to="/dashboard/organizer" replace />} />
            <Route path="/organizer/events" element={<ProtectedRoute requiredRole="organizer"><OrganizerEventListPage /></ProtectedRoute>} />
            <Route path="/organizer/events/create" element={<ProtectedRoute requiredRole="organizer"><EventCreatePage /></ProtectedRoute>} />
            <Route path="/organizer/events/:eventId/edit" element={<ProtectedRoute requiredRole="organizer"><EventEditPage /></ProtectedRoute>} />
            <Route path="/organizer/events/:eventId/inventory" element={<ProtectedRoute requiredRole="organizer"><TicketInventoryDashboardPage /></ProtectedRoute>} />
            <Route path="/organizer/events/:eventId/inventory/adjust" element={<ProtectedRoute requiredRole="organizer"><AdjustInventoryModal /></ProtectedRoute>} />
            <Route path="/organizer/events/:eventId/ticketing" element={<ProtectedRoute requiredRole="organizer"><TicketTierManagementPage /></ProtectedRoute>} />
            <Route path="/organizer/events/:eventId/ticketing/tier/:tierId/edit" element={<ProtectedRoute requiredRole="organizer"><TicketTierManagementPage /></ProtectedRoute>} />
            <Route path="/organizer/events/:eventId/pricing" element={<ProtectedRoute requiredRole="organizer"><EventPricingConfigPage /></ProtectedRoute>} />
            <Route path="/organizer/events/:eventId/pricing/preview" element={<ProtectedRoute requiredRole="organizer"><PricingPreviewModal /></ProtectedRoute>} />
            <Route path="/organizer/:organizerId" element={<OrganizerPublicProfilePage />} />
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
        </main>
      </div>
    </BrowserRouter>
  );
}


export default App;
