import React, { useState, useEffect, useCallback } from 'react';
import { Link, useParams } from 'react-router-dom';
import { api } from '../../../lib/api';

const DetailedAnalyticsPage = () => {
  const { eventId } = useParams();
  const [accessDenied, setAccessDenied] = useState(false);
  const [error, setError] = useState(null);

  const fetchAnalytics = useCallback(async () => {
    if (!eventId) return;
    try {
      await api.get(`/organizer/events/${eventId}/analytics`);
    } catch (err) {
      const status = err?.response?.status;
      if (status === 403) {
        setAccessDenied(true);
        setError(err?.response?.data?.message || 'You do not own this event.');
      } else if (status === 404) {
        setError('Event not found');
      }
    }
  }, [eventId]);

  useEffect(() => {
    fetchAnalytics();
  }, [fetchAnalytics]);

  if (accessDenied) {
    return (
      <div className="min-h-screen bg-[#F7F8FA] p-6 md:p-10">
        <div className="mx-auto max-w-xl text-center bg-white rounded-xl border border-[#E3E4E6] p-10 shadow-sm">
          <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-50 border border-red-100 text-2xl">🚫</div>
          <h2 className="text-2xl font-bold text-[#333333]">Access Denied</h2>
          <p className="mt-2 text-sm text-[#999999]">{error || 'You do not have permission to view analytics for this event.'}</p>
          <p className="mt-1 text-xs text-[#B3B3B3]">Event ID: {eventId}</p>
          <div className="mt-6 flex justify-center gap-3">
            <Link to="/organizer/events" className="inline-flex px-4 py-2 rounded-lg bg-[#FF6B6B] text-white text-sm font-semibold hover:bg-[#D94545]">← Back to Events</Link>
          </div>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen bg-[#F7F8FA] p-6 md:p-10">
        <div className="mx-auto max-w-xl text-center bg-white rounded-xl border border-[#E3E4E6] p-10 shadow-sm">
          <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-[#F7F8FA] border border-[#E3E4E6] text-2xl">⚠️</div>
          <h2 className="text-2xl font-bold text-[#333333]">Unable to load analytics</h2>
          <p className="mt-2 text-sm text-[#999999]">{error}</p>
          <div className="mt-6 flex justify-center gap-3">
            <Link to="/organizer/events" className="inline-flex px-4 py-2 rounded-lg bg-[#FF6B6B] text-white text-sm font-semibold hover:bg-[#D94545]">← Back to Events</Link>
            <button onClick={fetchAnalytics} className="px-4 py-2 rounded-lg border border-[#D1D2D4] bg-white text-sm">Retry</button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="mx-auto max-w-3xl">
        <Link
          to={`/organizer/events/${eventId}/analytics`}
          className="text-sm font-semibold text-indigo-600 hover:text-indigo-800"
        >
          ← Back to Analytics Dashboard
        </Link>
        <div className="mt-6 bg-white rounded-xl border border-slate-200 p-12 text-center shadow-sm">
          <div className="text-4xl mb-4">📊</div>
          <h1 className="text-xl font-bold text-slate-900 mb-2">Detailed Analytics</h1>
          <p className="text-sm text-slate-500 max-w-md mx-auto">
            Transaction tables and deep-dive breakdowns for event #{eventId}
            are coming soon. Summary metrics are available on the dashboard.
          </p>
        </div>
      </div>
    </div>
  );
};

export default DetailedAnalyticsPage;
