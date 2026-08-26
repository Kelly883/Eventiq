import React, { useEffect } from 'react';
import { useParams, useNavigate, Navigate, Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { ticketKeys } from '../../../lib/queryKeys';
import { api } from '../../../lib/api';

const TicketDetailPage = () => {
  const { ticketId } = useParams();
  const navigate = useNavigate();

  if (!ticketId) {
    return <Navigate to="/my-tickets" replace />;
  }

  const { data, isLoading, isError, error } = useQuery(
    ticketKeys.detail(ticketId),
    async () => {
      const response = await api.get(`/tickets/${ticketId}`);
      return response.data;
    },
    {
      staleTime: 5 * 60 * 1000,
      cacheTime: 30 * 60 * 1000,
      retry: 2,
    }
  );

  if (isError || !data) {
    const isNotFound = error?.response?.status === 404;
    return (
      <div className="text-center py-12 px-4 bg-white rounded-xl border border-slate-200">
        <div className="text-5xl mb-4">{isNotFound ? '🔍' : '⚠️'}</div>
        <h2 className="text-xl font-bold text-slate-900 mb-2">
          {isNotFound ? 'Ticket Not Found' : 'Something Went Wrong'}
        </h2>
        <p className="text-slate-500 mb-6 max-w-sm mx-auto">
          {isNotFound
            ? `We couldn't find a ticket with ID "${ticketId}". It may have been removed or the ID might be incorrect.`
            : 'We were unable to load the ticket details. Please try again later.'}
        </p>
        <div className="flex flex-col sm:flex-row gap-3 justify-center">
          <button
            onClick={() => navigate('/my-tickets', { replace: true })}
            className="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition-colors"
          >
            ← Back to My Tickets
          </button>
          <Link
            to="/my-tickets/status"
            className="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-white border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors"
          >
            🔍 Look Up by Code
          </Link>
        </div>
      </div>
    );
  }

  if (isLoading) {
    return (
      <div className="py-8 px-4">
        <div className="h-64 w-96 mb-4 bg-slate-200 rounded animate-pulse" />
        <div className="h-16 w-80 mb-2 bg-slate-200 rounded animate-pulse" />
        <div className="h-10 w-60 mb-2 bg-slate-200 rounded animate-pulse" />
        <div className="h-8 w-40 bg-slate-200 rounded animate-pulse" />
        <button
          onClick={() => navigate('/my-tickets', { replace: true })}
          className="mt-4 px-3 py-1 text-sm text-slate-400 cursor-not-allowed"
          disabled
        >
          Loading ticket details…
        </button>
      </div>
    );
  }

  const { id, code, status, createdAt, events, ...rest } = data;

  return (
    <div className="py-6 px-4 bg-white rounded-xl border border-slate-200">
      <div className="mb-4">
        <p className="text-xl font-bold text-slate-800">
          Ticket #{id}
        </p>
        <p className="text-sm text-slate-500">
          Code: {code}
        </p>
      </div>

      <div className="mb-6">
        <p className="text-sm text-slate-500">
          Status: {status}
        </p>
        <p className="text-sm text-slate-500">
          Created: {createdAt ? new Date(createdAt).toLocaleDateString() : 'N/A'}
        </p>
      </div>

      <div>
        <p className="text-sm text-slate-500">
          Events: {events?.length || 0}{' '}
          {(events?.length || 0) > 1 && (
            <small>
              ({events.length} activities)
            </small>
          )}
        </p>
      </div>
    </div>
  );
};

export default TicketDetailPage;
