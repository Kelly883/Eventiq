import React, { useState, useEffect } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { useAuthContext } from '../../auth/context/AuthContext';
import { api } from '../../../lib/api';
import Skeleton from '../../../components/Skeleton';

const CheckInStatsPage = () => {
  const { user } = useAuthContext();
  const [searchParams] = useSearchParams();
  const eventId = searchParams.get('eventId');
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    if (!user) return;

    const fetchStats = async () => {
      setLoading(true);
      try {
        const response = await api.get(`/check-in/stats`, {
          params: { event_id: eventId },
        });
        setStats(response.data?.data || response.data);
      } catch (err) {
        setError(err?.response?.data?.message || 'Failed to load statistics.');
      } finally {
        setLoading(false);
      }
    };

    fetchStats();
  }, [user, eventId]);

  if (loading) {
    return (
      <div className="min-h-screen bg-slate-50 p-6 md:p-10">
        <div className="mx-auto max-w-4xl space-y-6">
          <Skeleton variant="text" className="h-8 w-48" />
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <Skeleton variant="card" className="h-24" />
            <Skeleton variant="card" className="h-24" />
            <Skeleton variant="card" className="h-24" />
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
        <div className="flex items-center gap-4">
          <Link
            to={eventId ? `/check-in?eventId=${eventId}` : '/check-in'}
            className="text-slate-500 hover:text-slate-700 text-sm font-medium"
          >
            ← Back to Check-In
          </Link>
        </div>

        <div>
          <h1 className="text-2xl font-extrabold text-slate-900">Check-In Statistics</h1>
          <p className="text-sm text-slate-500 mt-1">
            Real-time check-in metrics for your event.
          </p>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-3 gap-5">
          <div className="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
            <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider block">
              Total Tickets
            </span>
            <span className="text-3xl font-black block mt-1">{stats?.total ?? 0}</span>
          </div>
          <div className="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
            <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider block">
              Checked In
            </span>
            <span className="text-3xl font-black block mt-1 text-green-600">{stats?.checked_in ?? 0}</span>
          </div>
          <div className="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
            <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider block">
              Remaining
            </span>
            <span className="text-3xl font-black block mt-1 text-amber-600">
              {stats?.total ? stats.total - (stats?.checked_in ?? 0) : 0}
            </span>
          </div>
        </div>

        <div className="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
          <h2 className="font-bold text-slate-800 text-sm mb-4">Check-In Rate</h2>
          <div className="w-full bg-slate-100 rounded-full h-4 overflow-hidden">
            <div
              className="bg-indigo-600 h-full rounded-full transition-all duration-500"
              style={{
                width: `${stats?.total ? ((stats?.checked_in ?? 0) / stats.total) * 100 : 0}%`,
              }}
            />
          </div>
          <p className="text-sm text-slate-500 mt-2">
            {stats?.total ? Math.round(((stats?.checked_in ?? 0) / stats.total) * 100) : 0}% of attendees checked in
          </p>
        </div>
      </div>
    </div>
  );
};

export default CheckInStatsPage;
