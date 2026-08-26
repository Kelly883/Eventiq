import React from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { ticketKeys } from '../../../lib/queryKeys';
import { api } from '../../../lib/api';

const UserTicketsDashboardPage = () => {
  const { data, isLoading, isError, error } = useQuery(
    ticketKeys.lists(),
    async () => {
      const response = await api.get('/tickets');
      return response.data;
    },
    {
      staleTime: 2 * 60 * 1000,
      cacheTime: 10 * 60 * 1000,
    }
  );

  const tickets = data?.data || data || [];
  const hasTickets = Array.isArray(tickets) && tickets.length > 0;

  if (isLoading) {
    return (
      <div className="space-y-4">
        {[1, 2, 3].map((i) => (
          <div key={i} className="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
            <div className="flex items-start gap-4">
              <div className="h-12 w-12 rounded-lg bg-slate-200 animate-pulse" />
              <div className="flex-1 space-y-2">
                <div className="h-5 w-32 bg-slate-200 rounded animate-pulse" />
                <div className="h-4 w-48 bg-slate-200 rounded animate-pulse" />
                <div className="h-4 w-24 bg-slate-200 rounded animate-pulse" />
              </div>
            </div>
          </div>
        ))}
      </div>
    );
  }

  if (isError) {
    return (
      <div className="text-center py-12 px-4 bg-white rounded-xl border border-slate-200">
        <div className="text-5xl mb-4">⚠️</div>
        <h2 className="text-xl font-bold text-slate-900 mb-2">Unable to Load Tickets</h2>
        <p className="text-slate-500 mb-6 max-w-sm mx-auto">
          {error?.response?.data?.message || 'Something went wrong while loading your tickets. Please try again.'}
        </p>
        <button
          onClick={() => window.location.reload()}
          className="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition-colors"
        >
          🔄 Try Again
        </button>
      </div>
    );
  }

  if (!hasTickets) {
    return (
      <div className="text-center py-12 px-4">
        <div className="mx-auto max-w-md">
          <div className="text-6xl mb-4">🎫</div>
          <h2 className="text-xl font-bold text-slate-900 mb-2">No tickets yet</h2>
          <p className="text-slate-500 mb-2">
            When you purchase tickets for events, they'll appear here.
          </p>
          <p className="text-sm text-slate-400 mb-6">
            Tickets give you access to events, show your seat or entry details, and can be checked in at the venue.
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
    <div className="space-y-4">
      <div className="flex items-center justify-between mb-2">
        <p className="text-sm text-slate-500">
          {tickets.length} ticket{tickets.length !== 1 ? 's' : ''}
        </p>
        <Link
          to="/my-tickets/status"
          className="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
        >
          🔍 Look up by code
        </Link>
      </div>
      {tickets.map((ticket) => (
        <Link
          key={ticket.id}
          to={`/my-tickets/${ticket.id}`}
          className="block bg-white rounded-xl border border-slate-200 p-5 shadow-sm hover:border-indigo-300 hover:shadow-md transition-all group"
        >
          <div className="flex items-start gap-4">
            <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-50 text-2xl group-hover:bg-indigo-100 transition-colors">
              🎫
            </div>
            <div className="flex-1 min-w-0">
              <div className="flex items-center justify-between gap-2">
                <h3 className="font-semibold text-slate-900 group-hover:text-indigo-700 transition-colors truncate">
                  Ticket #{ticket.id}
                </h3>
                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                  ticket.status === 'active' ? 'bg-green-100 text-green-800' :
                  ticket.status === 'used' ? 'bg-slate-100 text-slate-800' :
                  ticket.status === 'cancelled' ? 'bg-red-100 text-red-800' :
                  'bg-blue-100 text-blue-800'
                }`}>
                  {ticket.status || 'Unknown'}
                </span>
              </div>
              <p className="text-sm text-slate-500 mt-1 truncate">
                {ticket.event?.name || ticket.event_name || 'Event'}
              </p>
              <div className="flex items-center gap-4 mt-2 text-xs text-slate-400">
                {ticket.code && <span>Code: {ticket.code}</span>}
                {ticket.created_at && (
                  <span>{new Date(ticket.created_at).toLocaleDateString()}</span>
                )}
              </div>
            </div>
            <div className="text-slate-300 group-hover:text-indigo-500 transition-colors">
              →
            </div>
          </div>
        </Link>
      ))}
    </div>
  );
};

export default UserTicketsDashboardPage;
