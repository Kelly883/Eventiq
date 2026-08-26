import React from 'react';
import { Link } from 'react-router-dom';

const UserTicketsDashboardPage = () => {
  const hasTickets = false;

  if (!hasTickets) {
    return (
      <div className="text-center py-12 px-4">
        <div className="mx-auto max-w-md">
          <div className="text-6xl mb-4">🎫</div>
          <h2 className="text-xl font-bold text-slate-900 mb-2">No tickets yet</h2>
          <p className="text-slate-500 mb-6">
            When you purchase tickets, they'll appear here. Start by browsing upcoming events!
          </p>
          <div className="flex flex-col sm:flex-row gap-3 justify-center">
            <Link
              to="/events"
              className="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition-colors"
            >
              📋 Browse Events
            </Link>
            <Link
              to="/my-tickets/status"
              className="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-white border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors"
            >
              🔍 Check Existing Ticket
            </Link>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div>
      <p className="text-slate-500">Your tickets will appear here.</p>
    </div>
  );
};

export default UserTicketsDashboardPage;
