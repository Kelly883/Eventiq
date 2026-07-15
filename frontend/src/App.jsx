import React from 'react';
import { BrowserRouter, Routes, Route, NavLink, Navigate } from 'react-router-dom';
import SalesAnalyticsDashboardPage from './features/analytics/pages/SalesAnalyticsDashboardPage';
import { OrganizerDashboardPage, UserDashboardPage } from './features/dashboard/pages';
import { CheckInDashboardPage } from './features/check-in';
import VenueCheckInPage from './features/qr-code-ticketing/pages/VenueCheckInPage';
import EventBrowsePage from './features/events/pages/EventBrowsePage';
import EventDetailPage from './features/events/pages/EventDetailPage';
import CategoryBrowsePage from './features/events/pages/CategoryBrowsePage';
import EventCalendarPage from './features/events-calendar/pages/EventCalendarPage';
import TicketStatusPage from './features/ticket-delivery/pages/TicketStatusPage';
import ToastContainer from './features/notifications/components/ToastContainer';
import './App.css';

function App() {
  return (
    <BrowserRouter>
      <div className="flex flex-col min-h-screen bg-slate-50 font-sans">
        <ToastContainer />
        {/* Navigation Bar */}
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
                  to="/dashboard/user"
                  className={({ isActive }) =>
                    `px-3.5 py-2 rounded-lg text-xs sm:text-sm font-semibold transition-all flex items-center gap-1.5 ${
                      isActive
                        ? 'bg-indigo-50 text-indigo-600 shadow-sm shadow-indigo-100/40 border border-indigo-100/50'
                        : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80'
                    }`
                  }
                >
                  👤 User
                </NavLink>
              </nav>
            </div>
          </div>
        </header>

        {/* Page Content */}
        <main className="flex-1">
          <Routes>
            <Route path="/" element={<Navigate to="/analytics" replace />} />
            <Route path="/events" element={<EventBrowsePage />} />
            <Route path="/events/calendar" element={<EventCalendarPage />} />
            <Route path="/tickets/:ticketId/status" element={<TicketStatusPage />} />
            <Route path="/events/category/:categoryId" element={<CategoryBrowsePage />} />
            <Route path="/events/:eventId" element={<EventDetailPage />} />
            <Route path="/analytics" element={<SalesAnalyticsDashboardPage />} />
            <Route path="/analytics/:eventId" element={<SalesAnalyticsDashboardPage />} />
            <Route path="/dashboard/organizer" element={<OrganizerDashboardPage />} />
            <Route path="/check-in" element={<CheckInDashboardPage />} />
            <Route path="/venue-scan" element={<VenueCheckInPage />} />
            <Route path="/dashboard/user" element={<UserDashboardPage />} />
            <Route path="*" element={<Navigate to="/analytics" replace />} />
          </Routes>
        </main>
      </div>
    </BrowserRouter>
  );
}


export default App;


