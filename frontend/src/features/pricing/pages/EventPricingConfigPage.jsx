import React, { useState, useEffect, useCallback } from 'react';
import { Link, useParams, useNavigate } from 'react-router-dom';
import { api, showToast } from '../../../lib/api';
import Skeleton from '../../../components/Skeleton';

const EventPricingConfigPage = () => {
  const { eventId } = useParams();
  const navigate = useNavigate();
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
    if (eventId === 'invalid-id' || isNaN(Number(eventId))) {
      // Let backend decide, but quick client guard for obviously invalid
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
      if (status === 401) {
        setError('Session expired — please log in again.');
        return;
      }
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

  // Handle user loses access while on page (e.g., role revoked) — poll or on focus
  useEffect(() => {
    const onFocus = () => {
      // Re-validate on window focus
      if (event && !loading) fetchEvent();
    };
    window.addEventListener('focus', onFocus);
    return () => window.removeEventListener('focus', onFocus);
  }, [event, loading, fetchEvent]);

  if (loading) {
    return (
      <div className="min-h-screen bg-[#F7F8FA] p-6 md:p-10">
        <div className="mx-auto max-w-4xl space-y-6">
          <Skeleton variant="card" count={1} />
          <Skeleton variant="table" count={3} />
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
          <p className="mt-2 text-sm text-[#999999]">{error || 'You do not have permission to manage pricing for this event.'}</p>
          <p className="mt-1 text-xs text-[#B3B3B3]">Event ID: {eventId}</p>
          <div className="mt-6 flex justify-center gap-3">
            <Link to="/organizer/events" className="inline-flex px-4 py-2 rounded-lg bg-[#FF6B6B] text-white text-sm font-semibold hover:bg-[#D94545]">← Back to Events</Link>
            <button onClick={() => navigate(-1)} className="px-4 py-2 rounded-lg border border-[#D1D2D4] bg-white text-sm font-medium">Go Back</button>
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
          <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-[#F7F8FA] border border-[#E3E4E6] text-2xl">{is404 ? '🔍' : '⚠️'}</div>
          <h2 className="text-2xl font-bold text-[#333333]">{is404 ? 'Event Not Found' : 'Unable to load event'}</h2>
          <p className="mt-2 text-sm text-[#999999]">{error}</p>
          <p className="mt-1 text-xs text-[#B3B3B3]">Event ID: {eventId}</p>
          <div className="mt-6 flex justify-center gap-3">
            <Link to="/organizer/events" className="inline-flex px-4 py-2 rounded-lg bg-[#FF6B6B] text-white text-sm font-semibold hover:bg-[#D94545]">← Back to Events</Link>
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
        <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-2">
          <div>
            <h1 className="text-3xl font-bold text-[#333333] tracking-tight" style={{ fontFamily: 'Inter, system-ui, sans-serif' }}>
              Pricing Configuration
            </h1>
            <p className="mt-1 text-sm text-[#999999]">
              {event?.title ? (
                <>Configuring pricing for <span className="font-semibold text-[#333333]">{event.title}</span> • ID: {eventId}</>
              ) : (
                <>Event ID: {eventId} • Windows, early-bird & rules</>
              )}
            </p>
          </div>
          <Link
            to={`/organizer/events/${eventId}/pricing/preview`}
            className="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-[#FF6B6B] text-white text-sm font-semibold hover:bg-[#D94545] shadow-sm"
          >
            👁️ Preview
          </Link>
        </div>

        <div className="bg-white rounded-xl border border-[#E3E4E6] p-1.5 mb-6 shadow-sm flex flex-wrap gap-1">
          <Link to={`/organizer/events/${eventId}/ticketing`} className="px-4 py-2 rounded-lg text-sm font-medium text-[#333333] hover:bg-[#F7F8FA] border border-transparent">
            🎟️ Ticket Tiers
          </Link>
          <Link to={`/organizer/events/${eventId}/inventory`} className="px-4 py-2 rounded-lg text-sm font-medium text-[#333333] hover:bg-[#F7F8FA] border border-transparent">
            📦 Inventory
          </Link>
          <Link to={`/organizer/events/${eventId}/pricing`} className="px-4 py-2 rounded-lg text-sm font-semibold bg-[#FF6B6B] text-white shadow-sm" aria-current="page">
            💰 Pricing
          </Link>
          <Link to={`/organizer/events/${eventId}/edit`} className="px-4 py-2 rounded-lg text-sm font-medium text-[#333333] hover:bg-[#F7F8FA] border border-transparent">
            ✎ Edit Event
          </Link>
          <span className="ml-auto hidden md:inline-flex items-center text-xs text-[#999999] px-2">Pricing = windows & rules</span>
        </div>

        {event && (
          <div className="bg-white rounded-xl border border-[#E3E4E6] p-4 mb-6 shadow-sm flex items-center justify-between">
            <div className="text-sm">
              <p className="font-semibold text-[#333333]">{event.title}</p>
              <p className="text-xs text-[#999999]">{event.venue_name || event.venueName || 'No venue'} • {event.start_date || event.startDate || ''}</p>
            </div>
            <span className={`px-2 py-1 rounded text-xs font-medium border ${event.status === 'draft' ? 'bg-[#FFDA6B]/30 border-[#FFDA6B]' : 'bg-[#4ECDC4]/10 border-[#4ECDC4]/20'}`}>
              {event.status || 'published'}
            </span>
          </div>
        )}

        <div className="bg-white rounded-xl border border-[#E3E4E6] p-8 shadow-sm">
          <div className="flex items-start justify-between gap-4">
            <div>
              <h2 className="text-lg font-semibold text-[#333333]">Pricing windows</h2>
              <p className="mt-1 text-sm text-[#999999]">Configure early-bird, sales windows and tier pricing rules for this event.</p>
            </div>
            <Link
              to={`/organizer/events/${eventId}/pricing/preview`}
              className="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-[#FF6B6B] text-[#FF6B6B] text-sm font-medium hover:bg-[#FF6B6B] hover:text-white transition-colors"
            >
              👁️ Preview
            </Link>
          </div>

          <div className="mt-6 grid gap-4">
            <div className="rounded-lg border border-dashed border-[#D1D2D4] bg-[#F7F8FA] p-6 text-center">
              <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-white border border-[#E3E4E6]">💰</div>
              <h3 className="text-sm font-semibold text-[#333333]">No pricing windows yet</h3>
              <p className="mt-1 text-xs text-[#999999] max-w-md mx-auto">Create windows to schedule price changes. Preview shows how buyers will see prices.</p>
              <button
                type="button"
                onClick={() => showToast('Coming soon', 'Pricing window creation is coming soon.', 'info')}
                className="mt-4 inline-flex px-4 py-2 rounded-lg bg-[#FF6B6B] text-white text-sm font-semibold hover:bg-[#D94545]"
              >
                + Add Pricing Window
              </button>
            </div>
          </div>

          <div className="mt-6 flex justify-between items-center pt-6 border-t border-[#E3E4E6]">
            <p className="text-xs text-[#B3B3B3]">Deep link: /organizer/events/{eventId}/pricing</p>
            <Link to={`/organizer/events/${eventId}/pricing/preview`} className="text-sm font-medium text-[#FF6B6B] hover:text-[#D94545]">
              Preview →
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
};

export default EventPricingConfigPage;
