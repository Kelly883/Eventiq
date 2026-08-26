import React, { useState, useEffect, useCallback } from 'react';
import { Link, useParams, useOutletContext, Outlet, useLocation } from 'react-router-dom';
import { api } from '../../../lib/api';

export const useEventContext = () => useOutletContext();

const OrganizerEventLayout = () => {
  const { eventId } = useParams();
  const location = useLocation();
  const [event, setEvent] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const fetchEvent = useCallback(async () => {
    if (!eventId) {
      setError('Missing event ID');
      setLoading(false);
      return;
    }
    setLoading(true);
    setError(null);
    try {
      const res = await api.get(`/organizer/events/${eventId}`);
      const data = res.data?.event || res.data?.data || res.data;
      if (!data || !data.id) {
        setError('Event not found');
        return;
      }
      setEvent(data);
    } catch (err) {
      const status = err?.response?.status;
      if (status === 403) {
        setError(err?.response?.data?.message || 'You do not own this event.');
      } else if (status === 404) {
        setError('Event not found — it may have been deleted or the ID is invalid.');
      } else {
        setError(err?.response?.data?.message || 'Failed to load event');
      }
    } finally {
      setLoading(false);
    }
  }, [eventId]);

  useEffect(() => {
    fetchEvent();
  }, [fetchEvent]);

  const tabs = [
    { to: `/organizer/events/${eventId}`, label: 'Overview', icon: '📋', end: true },
    { to: `/organizer/events/${eventId}/ticketing`, label: 'Ticket Tiers', icon: '🎟️' },
    { to: `/organizer/events/${eventId}/inventory`, label: 'Inventory', icon: '📦' },
    { to: `/organizer/events/${eventId}/pricing`, label: 'Pricing', icon: '💰' },
    { to: `/organizer/events/${eventId}/analytics`, label: 'Analytics', icon: '📈' },
    { to: `/organizer/events/${eventId}/edit`, label: 'Edit', icon: '✎' },
  ];

  const isActiveTab = (tab) => {
    if (tab.end) {
      return location.pathname === tab.to;
    }
    return location.pathname.startsWith(tab.to);
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-[#F7F8FA] p-6 md:p-10">
        <div className="mx-auto max-w-4xl">
          <div className="animate-pulse">
            <div className="h-8 bg-slate-200 rounded w-48 mb-4"></div>
            <div className="h-4 bg-slate-200 rounded w-64 mb-8"></div>
            <div className="h-64 bg-slate-200 rounded-xl"></div>
          </div>
        </div>
      </div>
    );
  }

  if (error) {
    const is404 = error.toLowerCase().includes('not found') || error.toLowerCase().includes('invalid');
    return (
      <div className="min-h-screen bg-[#F7F8FA] p-6 md:p-10">
        <div className="mx-auto max-w-xl text-center bg-white rounded-xl border border-[#E3E4E6] p-10 shadow-sm">
          <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-[#F7F8FA] border border-[#E3E4E6] text-2xl">
            {is404 ? '🔍' : '⚠️'}
          </div>
          <h2 className="text-2xl font-bold text-[#333333]">
            {is404 ? 'Event Not Found' : 'Unable to load event'}
          </h2>
          <p className="mt-2 text-sm text-[#999999]">{error}</p>
          <p className="mt-1 text-xs text-[#B3B3B3]">Event ID: {eventId}</p>
          <div className="mt-6 flex justify-center gap-3">
            <Link
              to="/organizer/events"
              className="inline-flex px-4 py-2 rounded-lg bg-[#FF6B6B] text-white text-sm font-semibold"
            >
              ← Back to Events
            </Link>
            <button
              onClick={fetchEvent}
              className="px-4 py-2 rounded-lg border border-[#D1D2D4] bg-white text-sm"
            >
              Retry
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-[#F7F8FA] p-6 md:p-10">
      <div className="mx-auto max-w-4xl">
        <Link
          to="/organizer/events"
          className="inline-flex items-center gap-2 text-sm font-medium text-[#999999] hover:text-[#333333] mb-3"
        >
          ← Back to Events
        </Link>

        <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-2">
          <div>
            <h1 className="text-3xl font-bold text-[#333333] tracking-tight" style={{ fontFamily: 'Inter, system-ui, sans-serif' }}>
              {event?.title || 'Event'}
            </h1>
            <p className="mt-1 text-sm text-[#999999]">
              ID: {eventId} • {event?.status || 'published'}
            </p>
          </div>
          <span
            className={`px-2 py-1 rounded text-xs font-medium border self-start ${
              event?.status === 'draft'
                ? 'bg-[#FFDA6B]/30 border-[#FFDA6B]'
                : 'bg-[#4ECDC4]/10 border-[#4ECDC4]/20'
            }`}
          >
            {event?.status || 'published'}
          </span>
        </div>

        <div className="bg-white rounded-xl border border-[#E3E4E6] p-1.5 mb-6 shadow-sm flex flex-wrap gap-1">
          {tabs.map((tab) => (
            <Link
              key={tab.to}
              to={tab.to}
              className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                isActiveTab(tab)
                  ? 'bg-[#FF6B6B] text-white shadow-sm'
                  : 'text-[#333333] hover:bg-[#F7F8FA] border border-transparent'
              }`}
            >
              {tab.icon} {tab.label}
            </Link>
          ))}
        </div>

        <Outlet context={{ event, eventId, refreshEvent: fetchEvent }} />
      </div>
    </div>
  );
};

export default OrganizerEventLayout;
