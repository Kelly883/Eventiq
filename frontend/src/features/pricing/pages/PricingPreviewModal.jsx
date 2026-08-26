import React, { useEffect, useState, useCallback } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { api } from '../../../lib/api';
import Skeleton from '../../../components/Skeleton';

const PricingPreviewModal = () => {
  const { eventId } = useParams();
  const navigate = useNavigate();
  const [event, setEvent] = useState(null);
  const [windows, setWindows] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const close = useCallback(() => {
    navigate(`/organizer/events/${eventId}/pricing`);
  }, [navigate, eventId]);

  // Close on Esc
  useEffect(() => {
    const onKey = (e) => {
      if (e.key === 'Escape') close();
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [close]);

  // Fetch event + pricing windows for preview
  useEffect(() => {
    let cancelled = false;
    async function fetchPreview() {
      if (!eventId) {
        setError('Missing event ID');
        setLoading(false);
        return;
      }
      setLoading(true);
      try {
        const [eventRes, windowsRes] = await Promise.allSettled([
          api.get(`/organizer/events/${eventId}`),
          api.get(`/organizer/events/${eventId}/pricing-windows`),
        ]);
        if (cancelled) return;
        if (eventRes.status === 'fulfilled') {
          const data = eventRes.value.data?.event || eventRes.value.data?.data || eventRes.value.data;
          setEvent(data);
        } else {
          const status = eventRes.reason?.response?.status;
          if (status === 404) setError('Event not found');
          else if (status === 403) setError('Access denied — you do not own this event');
          else setError(eventRes.reason?.response?.data?.message || 'Failed to load event');
        }
        if (windowsRes.status === 'fulfilled') {
          const w = windowsRes.value.data?.data || windowsRes.value.data || [];
          setWindows(Array.isArray(w) ? w : w.data || []);
        }
      } catch (err) {
        if (!cancelled) setError(err?.response?.data?.message || 'Failed to load preview');
      } finally {
        if (!cancelled) setLoading(false);
      }
    }
    fetchPreview();
    return () => { cancelled = true; };
  }, [eventId]);

  // Deep linking works because this is a route; unauth/403 handled by ProtectedRoute + error above

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      {/* Backdrop — clicking returns to pricing */}
      <button
        type="button"
        aria-label="Close preview"
        onClick={close}
        className="absolute inset-0 bg-black/50 backdrop-blur-sm"
      />
      {/* Modal */}
      <div className="relative w-full max-w-2xl bg-white rounded-2xl shadow-xl border border-[#E3E4E6] max-h-[90vh] overflow-auto">
        {/* Tab bar — always visible so organizer knows where they are */}
        <div className="bg-white border-b border-[#E3E4E6] px-6 py-2 flex flex-wrap gap-1 rounded-t-2xl">
          <Link to={`/organizer/events/${eventId}/ticketing`} className="px-4 py-2 rounded-lg text-sm font-medium text-[#333333] hover:bg-[#F7F8FA] border border-transparent">🎟️ Ticket Tiers</Link>
          <Link to={`/organizer/events/${eventId}/inventory`} className="px-4 py-2 rounded-lg text-sm font-medium text-[#333333] hover:bg-[#F7F8FA] border border-transparent">📦 Inventory</Link>
          <Link to={`/organizer/events/${eventId}/pricing`} className="px-4 py-2 rounded-lg text-sm font-semibold bg-[#FF6B6B] text-white shadow-sm" aria-current="page">💰 Pricing</Link>
        </div>
        <div className="sticky top-0 bg-white border-b border-[#E3E4E6] px-6 py-4 flex items-center justify-between rounded-t-2xl">
          <div>
            <h2 className="text-lg font-bold text-[#333333]">Pricing Preview</h2>
            <p className="text-xs text-[#999999]">Event ID: {eventId} {event?.title ? `• ${event.title}` : ''}</p>
          </div>
          <button
            type="button"
            onClick={close}
            className="h-8 w-8 inline-flex items-center justify-center rounded-full border border-[#E3E4E6] bg-[#F7F8FA] text-[#333333] hover:bg-white"
            aria-label="Close"
          >
            ✕
          </button>
        </div>

        <div className="p-6">
          {loading ? (
            <div className="space-y-4">
              <Skeleton variant="card" count={1} />
              <Skeleton variant="table" count={2} />
            </div>
          ) : error ? (
            <div className="text-center py-8">
              <p className="text-sm text-[#FF6B6B]">{error}</p>
              <div className="mt-4 flex justify-center gap-3">
                <button onClick={close} className="px-4 py-2 rounded-lg bg-[#FF6B6B] text-white text-sm font-semibold">Back to Pricing</button>
                <Link to="/organizer/events" className="px-4 py-2 rounded-lg border border-[#D1D2D4] bg-white text-sm">Back to Events</Link>
              </div>
            </div>
          ) : (
            <>
              <div className="bg-[#F7F8FA] rounded-xl border border-[#E3E4E6] p-4 mb-4">
                <h3 className="text-sm font-semibold text-[#333333]">{event?.title || `Event ${eventId}`}</h3>
                <p className="text-xs text-[#999999] mt-1">{event?.venue_name || event?.venueName || 'No venue'} • {event?.start_date || event?.startDate || 'No date'}</p>
                <span className="mt-2 inline-flex px-2 py-1 rounded text-xs font-medium bg-white border border-[#E3E4E6] text-[#999999]">
                  Preview as buyer will see it
                </span>
              </div>

              {windows.length === 0 ? (
                <div className="text-center py-8 border border-dashed border-[#D1D2D4] rounded-xl bg-[#F7F8FA]">
                  <p className="text-sm font-medium text-[#333333]">No pricing windows configured</p>
                  <p className="text-xs text-[#999999] mt-1">Buyers will see base tier prices.</p>
                </div>
              ) : (
                <div className="space-y-3">
                  {windows.map((w) => (
                    <div key={w.id} className="border border-[#E3E4E6] rounded-lg p-4 bg-white">
                      <div className="flex justify-between items-start">
                        <p className="text-sm font-semibold text-[#333333]">{w.name || `Window #${w.id}`}</p>
                        <span className="text-xs px-2 py-1 rounded bg-[#4ECDC4]/10 border border-[#4ECDC4]/20 text-[#333333]">{w.status || 'active'}</span>
                      </div>
                      <p className="text-xs text-[#999999] mt-1">
                        {w.sales_start_date || w.start_date || ''} → {w.sales_end_date || w.end_date || ''} • {w.price ? `Price: ${w.price}` : ''}
                      </p>
                    </div>
                  ))}
                </div>
              )}

              <div className="mt-6 flex gap-3">
                <button type="button" onClick={close} className="flex-1 inline-flex items-center justify-center px-4 py-2 rounded-lg bg-[#FF6B6B] text-white text-sm font-semibold hover:bg-[#D94545]">
                  ← Back to Pricing Config
                </button>
                <Link to={`/organizer/events/${eventId}/ticketing`} className="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-[#D1D2D4] bg-white text-sm font-medium hover:bg-[#F7F8FA]">
                  Ticket Tiers
                </Link>
              </div>
            </>
          )}
        </div>
      </div>
    </div>
  );
};

export default PricingPreviewModal;
