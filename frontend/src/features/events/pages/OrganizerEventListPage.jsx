import React from 'react';
import { Link } from 'react-router-dom';

const OrganizerEventListPage = () => {
  const eventId = 1;

  return (
    <div className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="mx-auto max-w-7xl">
        <div className="mb-8">
          <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">My Events</h1>
          <p className="mt-2 text-sm text-slate-500">Manage your upcoming, past, and draft events.</p>
        </div>

        <div className="bg-white p-6 rounded-xl border border-slate-100 shadow-sm">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-lg font-bold text-slate-800">Event Actions</h2>
          </div>
          <div className="flex flex-wrap gap-3">
            <Link
              to="/organizer/events/create"
              className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition-colors"
            >
              + Create New Event
            </Link>
            <Link
              to={`/organizer/events/${eventId}/ticketing`}
              className="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition-colors"
            >
              🎫 Ticket Tiers
            </Link>
            <Link
              to={`/organizer/events/${eventId}/pricing`}
              className="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition-colors"
            >
              💰 Pricing
            </Link>
            <Link
              to={`/organizer/events/${eventId}/inventory`}
              className="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition-colors"
            >
              📦 Inventory
            </Link>
          </div>
          <p className="mt-4 text-xs text-slate-400">
            Showing actions for Event ID: {eventId} (demo). Wire up event selection for full functionality.
          </p>
        </div>
      </div>
    </div>
  );
};

export default OrganizerEventListPage;
