import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import Skeleton from '../../../components/Skeleton';
import { api } from '../../../lib/api';

const VenueStaffDashboardPage = () => {
  const navigate = useNavigate();
  const [stats, setStats] = useState({ totalEvents: 0, activeEvents: 0 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    let cancelled = false;
    async function fetchDashboardData() {
      setLoading(true);
      setError(null);
      try {
        const eventsRes = await api.get('/events', { params: { venue_access: 'true' } });
        if (!cancelled) {
          const events = eventsRes.data?.events || eventsRes.data?.data || [];
          const list = Array.isArray(events) ? events : [];
          setStats({
            totalEvents: list.length,
            activeEvents: list.filter(e => e.status === 'active').length,
          });
        }
      } catch (err) {
        if (!cancelled) setError('Failed to load venue dashboard');
      } finally {
        if (!cancelled) setLoading(false);
      }
    }
    fetchDashboardData();
    return () => { cancelled = true; };
  }, []);

  if (loading) {
    return (
      <div className="min-h-screen bg-slate-50 p-6 md:p-10">
        <div className="mx-auto max-w-7xl">
          <Skeleton lines={6} />
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="mx-auto max-w-7xl">
        <div className="mb-8">
          <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">
            Venue Staff Dashboard
          </h1>
          <p className="mt-2 text-slate-600">
            Welcome back! Manage event check-ins and attendee scanning.
          </p>
        </div>

        {error && (
          <div className="mb-6 rounded-md border border-red-200 bg-red-50 p-4">
            <div className="text-sm text-red-800">{error}</div>
          </div>
        )}

        <div className="mb-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          <div
            className="cursor-pointer rounded-lg border border-slate-200 bg-white p-6 shadow-sm hover:shadow-md"
            onClick={() => navigate('/venue/events')}
          >
            <p className="text-sm font-medium text-slate-600">Total Events</p>
            <p className="mt-2 text-3xl font-bold text-slate-900">{stats.totalEvents}</p>
          </div>
          <div
            className="cursor-pointer rounded-lg border border-slate-200 bg-white p-6 shadow-sm hover:shadow-md"
            onClick={() => navigate('/venue/events?filter=active')}
          >
            <p className="text-sm font-medium text-slate-600">Active Events</p>
            <p className="mt-2 text-3xl font-bold text-slate-900">{stats.activeEvents}</p>
          </div>
          <div
            className="cursor-pointer rounded-lg border border-slate-200 bg-white p-6 shadow-sm hover:shadow-md"
            onClick={() => navigate('/venue/events')}
          >
            <p className="text-sm font-medium text-slate-600">Start Check-In</p>
            <p className="mt-2 text-sm text-slate-500">Select an event to begin</p>
          </div>
        </div>

        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          <button
            onClick={() => navigate('/venue/events')}
            className="rounded-lg border border-slate-200 bg-white p-4 text-left shadow-sm hover:bg-slate-50"
          >
            <span className="text-sm font-medium text-slate-700">Event List</span>
          </button>
          <button
            onClick={() => navigate('/check-in')}
            className="rounded-lg border border-slate-200 bg-white p-4 text-left shadow-sm hover:bg-slate-50"
          >
            <span className="text-sm font-medium text-slate-700">Quick Check-In</span>
          </button>
          <button
            onClick={() => navigate('/check-in/stats')}
            className="rounded-lg border border-slate-200 bg-white p-4 text-left shadow-sm hover:bg-slate-50"
          >
            <span className="text-sm font-medium text-slate-700">View Stats</span>
          </button>
          <button
            onClick={() => navigate('/my-tickets')}
            className="rounded-lg border border-slate-200 bg-white p-4 text-left shadow-sm hover:bg-slate-50"
          >
            <span className="text-sm font-medium text-slate-700">My Tickets</span>
          </button>
        </div>
      </div>
    </div>
  );
};

export default VenueStaffDashboardPage;
