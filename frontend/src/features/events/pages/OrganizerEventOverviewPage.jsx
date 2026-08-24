import React, { useState, useEffect, useCallback } from 'react';
import { Link, useParams } from 'react-router-dom';
import { api } from '../../../lib/api';
import Skeleton from '../../../components/Skeleton';

const OrganizerEventOverviewPage = () => {
  const { eventId } = useParams();
  const [event, setEvent] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [accessDenied, setAccessDenied] = useState(false);

  const fetchEvent = useCallback(async () => {
    if (!eventId) {
      setError('Missing event ID');
      setLoading(false);
      return;
    }
    setLoading(true);
    setError(null);
    setAccessDenied(false);
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
        setAccessDenied(true);
        setError(err?.response?.data?.message || 'You do not own this event.');
        return;
      }
      if (status === 404) {
        setError('Event not found — it may have been deleted or the ID is invalid.');
        return;
      }
      setError(err?.response?.data?.message || 'Failed to load event');
    } finally {
      setLoading(false);
    }
  }, [eventId]);

  useEffect(() => {
    fetchEvent();
  }, [fetchEvent]);

  if (loading) {
    return (
      <div className="min-h-screen bg-[#F7F8FA] p-6 md:p-10">
        <div className="mx-auto max-w-4xl space-y-6">
          <Skeleton variant="card" count={1} />
          <Skeleton variant="table" count={2} />
        </div>
      </div>
    );
  }

  if (accessDenied) {
    return (
      <div className="min-h-screen bg-[#F7F8FA] p-6 md:p-10">
        <div className="mx-auto max-w-xl text-center bg-white rounded-xl border border-[#E3E4E6] p-10 shadow-sm">
          <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-50 border border-red-100 text-2xl">🚫</div>
          <h2 className="text-2xl font-bold text-[#333333]">Access Denied</h2>
          <p className="mt-2 text-sm text-[#999999]">{error}</p>
          <p className="mt-1 text-xs text-[#B3B3B3]">Event ID: {eventId}</p>
          <Link to="/organizer/events" className="mt-6 inline-flex px-4 py-2 rounded-lg bg-[#FF6B6B] text-white text-sm font-semibold hover:bg-[#D94545]">← Back to Events</Link>
        </div>
      </div>
    );
  }

  if (error) {
    const is404 = error.toLowerCase().includes('not found') || error.toLowerCase().includes('invalid');
    return (
      <div className="min-h-screen bg-[#F7F8FA] p-6 md:p-10">
        <div className="mx-auto max-w-xl text-center bg-white rounded-xl border border-[#E3E4E6] p-10 shadow-sm">
          <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-[#F7F8FA] border border-[#E3E4E6] text-2xl">{is404 ? '🔍' : '⚠️'}</div>
          <h2 className="text-2xl font-bold text-[#333333]">{is404 ? 'Event Not Found' : 'Unable to load event'}</h2>
          <p className="mt-2 text-sm text-[#999999]">{error}</p>
          <p className="mt-1 text-xs text-[#B3B3B3]">Event ID: {eventId}</p>
          <div className="mt-6 flex justify-center gap-3">
            <Link to="/organizer/events" className="inline-flex px-4 py-2 rounded-lg bg-[#FF6B6B] text-white text-sm font-semibold">← Back to Events</Link>
            <button onClick={fetchEvent} className="px-4 py-2 rounded-lg border border-[#D1D2D4] bg-white text-sm">Retry</button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-[#F7F8FA] p-6 md:p-10">
      <div className="mx-auto max-w-4xl">
        <Link to="/organizer/events" className="inline-flex items-center gap-2 text-sm font-medium text-[#999999] hover:text-[#333333] mb-3">
          ← Back to Events
        </Link>
        <h1 className="text-3xl font-bold text-[#333333] tracking-tight" style={{ fontFamily: 'Inter, system-ui, sans-serif' }}>
          {event.title}
        </h1>
        <p className="mt-1 text-sm text-[#999999]">Event overview • ID: {eventId} • {event.status || 'published'}</p>

        <div className="mt-6 bg-white rounded-xl border border-[#E3E4E6] p-4 shadow-sm flex items-center justify-between">
          <div className="text-sm">
            <p className="font-semibold text-[#333333]">{event.title}</p>
            <p className="text-xs text-[#999999]">{event.venue_name || event.venueName || 'No venue'} • {event.start_date || event.startDate || ''}</p>
          </div>
          <span className={`px-2 py-1 rounded text-xs font-medium border ${event.status === 'draft' ? 'bg-[#FFDA6B]/30 border-[#FFDA6B]' : 'bg-[#4ECDC4]/10 border-[#4ECDC4]/20'}`}>{event.status || 'published'}</span>
        </div>

        <div className="mt-6 grid md:grid-cols-2 gap-4">
          <Link to={`/organizer/events/${eventId}/edit`} className="bg-white rounded-xl border border-[#E3E4E6] p-6 shadow-sm hover:border-[#FF6B6B]/50 hover:shadow-md transition-all group">
            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-[#F7F8FA] border border-[#E3E4E6] group-hover:bg-[#FF6B6B] group-hover:text-white transition-colors">✎</div>
            <h3 className="mt-3 text-sm font-semibold text-[#333333]">Edit Event</h3>
            <p className="mt-1 text-xs text-[#999999]">Update title, description, venue, capacity and visibility.</p>
            <span className="mt-3 inline-flex text-xs font-medium text-[#FF6B6B]">Open →</span>
          </Link>
          <Link to={`/organizer/events/${eventId}/ticketing`} className="bg-white rounded-xl border border-[#E3E4E6] p-6 shadow-sm hover:border-[#FF6B6B]/50 hover:shadow-md transition-all group">
            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-[#F7F8FA] border border-[#E3E4E6] group-hover:bg-[#FF6B6B] group-hover:text-white transition-colors">🎟️</div>
            <h3 className="mt-3 text-sm font-semibold text-[#333333]">Ticket Tiers</h3>
            <p className="mt-1 text-xs text-[#999999]">What you sell — tiers, prices, descriptions.</p>
            <span className="mt-3 inline-flex text-xs font-medium text-[#FF6B6B]">Manage →</span>
          </Link>
          <Link to={`/organizer/events/${eventId}/inventory`} className="bg-white rounded-xl border border-[#E3E4E6] p-6 shadow-sm hover:border-[#FF6B6B]/50 hover:shadow-md transition-all group">
            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-[#F7F8FA] border border-[#E3E4E6] group-hover:bg-[#FF6B6B] group-hover:text-white transition-colors">📦</div>
            <h3 className="mt-3 text-sm font-semibold text-[#333333]">Inventory</h3>
            <p className="mt-1 text-xs text-[#999999]">How many — remaining stock and adjustments.</p>
            <span className="mt-3 inline-flex text-xs font-medium text-[#FF6B6B]">Adjust →</span>
          </Link>
          <Link to={`/organizer/events/${eventId}/pricing`} className="bg-white rounded-xl border border-[#E3E4E6] p-6 shadow-sm hover:border-[#FF6B6B]/50 hover:shadow-md transition-all group">
            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-[#F7F8FA] border border-[#E3E4E6] group-hover:bg-[#FF6B6B] group-hover:text-white transition-colors">💰</div>
            <h3 className="mt-3 text-sm font-semibold text-[#333333]">Pricing Windows</h3>
            <p className="mt-1 text-xs text-[#999999]">When and how much — sales windows & rules. Preview how buyers see prices.</p>
            <span className="mt-3 inline-flex text-xs font-medium text-[#FF6B6B]">Configure →</span>
          </Link>
        </div>

        <div className="mt-6 bg-white rounded-xl border border-[#E3E4E6] p-1.5 shadow-sm flex flex-wrap gap-1">
          <Link to={`/organizer/events/${eventId}/ticketing`} className="px-4 py-2 rounded-lg text-sm font-medium text-[#333333] hover:bg-[#F7F8FA] border border-transparent">🎟️ Ticket Tiers</Link>
          <Link to={`/organizer/events/${eventId}/inventory`} className="px-4 py-2 rounded-lg text-sm font-medium text-[#333333] hover:bg-[#F7F8FA] border border-transparent">📦 Inventory</Link>
          <Link to={`/organizer/events/${eventId}/pricing`} className="px-4 py-2 rounded-lg text-sm font-medium text-[#333333] hover:bg-[#F7F8FA] border border-transparent">💰 Pricing</Link>
          <Link to={`/organizer/events/${eventId}/edit`} className="px-4 py-2 rounded-lg text-sm font-medium text-[#333333] hover:bg-[#F7F8FA] border border-transparent">✎ Edit</Link>
          <span className="ml-auto hidden md:inline-flex items-center text-xs text-[#999999] px-2">Overview hub — reduces tab hopping</span>
        </div>

        <p className="mt-4 text-center text-xs text-[#B3B3B3]">Deep link: /organizer/events/{eventId}</p>
      </div>
    </div>
  );
};

export default OrganizerEventOverviewPage;
