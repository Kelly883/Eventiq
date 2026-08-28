import React, { useState, useEffect } from 'react';
import { Link, useSearchParams, useNavigate } from 'react-router-dom';
import EventSelector from '../../analytics/components/EventSelector';
import { useAuthContext } from '../../auth/context/AuthContext';
import { api } from '../../../lib/api';
import Skeleton from '../../../components/Skeleton';
import EmptyState from '../../../components/EmptyState';

const CheckInHistoryPage = () => {
  const { user } = useAuthContext();
  const [searchParams, setSearchParams] = useSearchParams();
  const navigate = useNavigate();
  const eventId = searchParams.get('eventId');
  const [history, setHistory] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    if (!user) return;

    const fetchHistory = async () => {
      setLoading(true);
      try {
        const response = await api.get(`/venue/check-ins/history`, {
          params: { event_id: eventId },
        });
        setHistory(response.data?.data || []);
      } catch (err) {
        setError(err?.response?.data?.message || 'Failed to load history.');
      } finally {
        setLoading(false);
      }
    };

    fetchHistory();
  }, [user, eventId]);

  if (loading) {
    return (
      <div className="min-h-screen bg-slate-50 p-6 md:p-10">
        <div className="mx-auto max-w-4xl space-y-6">
          <Skeleton variant="text" className="h-8 w-48" />
          <div className="space-y-3">
            <Skeleton variant="card" className="h-16" />
            <Skeleton variant="card" className="h-16" />
            <Skeleton variant="card" className="h-16" />
          </div>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen bg-slate-50 p-6 md:p-10">
        <div className="mx-auto max-w-4xl">
          <div className="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
            {error}
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="mx-auto max-w-4xl space-y-6">
        <div className="flex flex-col sm:flex-row sm:items-center gap-4 mb-6">
          <Link
            to={eventId ? `/check-in?eventId=${eventId}` : '/check-in'}
            className="text-slate-500 hover:text-slate-700 text-sm font-medium shrink-0"
          >
            ← Back to Check-In
          </Link>
          <div className="flex-1 max-w-xs">
            <EventSelector
              compact
              selectedEventId={eventId}
              onSelect={(id) => {
                if (id) setSearchParams({ eventId: id });
                else setSearchParams({});
              }}
            />
          </div>
        </div>
        {eventId && (
          <div className="flex items-center gap-2 px-4 py-2 bg-indigo-50 border border-indigo-100 rounded-xl text-sm text-indigo-800 mb-4">
            <span className="h-2 w-2 bg-emerald-500 rounded-full animate-pulse" />
            Showing history for: <span className="font-bold">Event #{eventId}</span>
          </div>
        )}
        {!eventId && (
          <div className="px-4 py-3 bg-amber-50 border border-amber-100 rounded-xl text-sm text-amber-800 mb-4">
            ⚠️ No event selected — showing history across all your events.
          </div>
        )}

        <div>
          <h1 className="text-2xl font-extrabold text-slate-900">Check-In History</h1>
          <p className="text-sm text-slate-500 mt-1">
            Audit log of all check-in actions for compliance.
          </p>
        </div>

        {history.length === 0 ? (
          <EmptyState
            icon="📋"
            title="No check-in history"
            description="Check-in actions will appear here as they occur."
          />
        ) : (
          <div className="space-y-3">
            {history.map((item) => (
              <div key={item.id} className="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="font-semibold text-slate-900">
                      {item.ticket_code || item.attendee_name || 'Unknown'}
                    </p>
                    <p className="text-sm text-slate-500">
                      By {item.staff_name || 'Staff'} • {new Date(item.created_at).toLocaleString()}
                    </p>
                  </div>
                  <span className={`px-3 py-1 rounded-full text-xs font-medium ${
                    item.action === 'check_in' ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800'
                  }`}>
                    {item.action === 'check_in' ? 'Check In' : item.action}
                  </span>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

export default CheckInHistoryPage;
