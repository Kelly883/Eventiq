import React, { useState, useEffect, useCallback } from 'react';
import { Link, useParams, useNavigate } from 'react-router-dom';
import { api, showToast } from '../../../lib/api';
import Skeleton from '../../../components/Skeleton';
import PricingPreviewModal from './PricingPreviewModal';

const EventPricingConfigPage = () => {
  const { eventId } = useParams();
  const navigate = useNavigate();
  const [event, setEvent] = useState(null);
  const [windows, setWindows] = useState([]);
  const [tiers, setTiers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [accessDenied, setAccessDenied] = useState(false);
  const [showForm, setShowForm] = useState(false);
  const [savingWindow, setSavingWindow] = useState(false);
  const [showPreviewModal, setShowPreviewModal] = useState(false);
  const [formWindow, setFormWindow] = useState({
    window_name: '',
    ticket_category_id: '',
    start_date_time: '',
    end_date_time: '',
    price: '',
    quantity_limit: '',
    is_active: true,
  });

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
      const [eventRes, windowsRes] = await Promise.allSettled([
        api.get(`/organizer/events/${eventId}`),
        api.get(`/organizer/events/${eventId}/pricing-windows`),
      ]);
      if (eventRes.status === 'fulfilled') {
        const data = eventRes.value.data?.event || eventRes.value.data?.data || eventRes.value.data;
        if (!data || !data.id) {
          setError('Event not found');
          return;
        }
        setEvent(data);
        const t = data.ticket_tiers || data.ticketTiers || [];
        setTiers(Array.isArray(t) ? t : []);
      } else {
        const status = eventRes.reason?.response?.status;
        if (status === 403) {
          setAccessDenied(true);
          setError(eventRes.reason?.response?.data?.message || 'You do not own this event.');
          return;
        }
        if (status === 404) {
          setError('Event not found — it may have been deleted or the ID is invalid.');
          return;
        }
        throw eventRes.reason;
      }
      if (windowsRes.status === 'fulfilled') {
        const w = windowsRes.value.data?.data || windowsRes.value.data || [];
        const list = Array.isArray(w) ? w : w.data || [];
        setWindows(list);
      }
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

  const handleCreateWindow = async (e) => {
    e.preventDefault();
    if (!formWindow.window_name.trim() || !formWindow.ticket_category_id || !formWindow.start_date_time || !formWindow.end_date_time || !formWindow.price) {
      showToast('Validation failed', 'Window name, tier, start/end and price are required', 'error');
      return;
    }
    setSavingWindow(true);
    try {
      await api.post(`/organizer/events/${eventId}/pricing-windows`, {
        window_name: formWindow.window_name,
        ticket_category_id: Number(formWindow.ticket_category_id),
        start_date_time: formWindow.start_date_time,
        end_date_time: formWindow.end_date_time,
        price: Number(formWindow.price),
        quantity_limit: formWindow.quantity_limit ? Number(formWindow.quantity_limit) : null,
        is_active: !!formWindow.is_active,
      });
      showToast('Pricing window created', 'Window created — preview now shows updated pricing.', 'success');
      setShowForm(false);
      setFormWindow({ window_name: '', ticket_category_id: '', start_date_time: '', end_date_time: '', price: '', quantity_limit: '', is_active: true });
      fetchEvent();
    } catch (err) {
      const msg = err?.response?.data?.message || JSON.stringify(err?.response?.data?.errors || {}) || 'Failed to create window';
      showToast('Create failed', msg, 'error');
    } finally {
      setSavingWindow(false);
    }
  };

  const handleDeleteWindow = async (winId) => {
    if (!window.confirm('Delete this pricing window?')) return;
    try {
      await api.delete(`/organizer/events/${eventId}/pricing-windows/${winId}`);
      showToast('Deleted', 'Pricing window deleted', 'success');
      fetchEvent();
    } catch (err) {
      showToast('Delete failed', err?.response?.data?.message || 'Could not delete', 'error');
    }
  };

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
          <button
            onClick={() => setShowPreviewModal(true)}
            className="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg bg-[#FF6B6B] text-white text-sm font-semibold hover:bg-[#D94545] shadow-sm"
          >
            👁️ Preview
          </button>
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
            <button
              onClick={() => setShowPreviewModal(true)}
              className="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-[#FF6B6B] text-[#FF6B6B] text-sm font-medium hover:bg-[#FF6B6B] hover:text-white transition-colors"
            >
              👁️ Preview
            </button>
          </div>

          <div className="mt-6 space-y-4">
            {windows.length > 0 ? (
              <div className="space-y-3">
                {windows.map((w) => (
                  <div key={w.id} className="border border-[#E3E4E6] rounded-lg p-4 bg-[#F7F8FA] flex justify-between items-start">
                    <div>
                      <p className="text-sm font-semibold text-[#333333]">{w.window_name || w.name || `Window #${w.id}`}</p>
                      <p className="text-xs text-[#999999] mt-1">Tier #{w.ticket_category_id} • {w.start_date_time || w.start_date} → {w.end_date_time || w.end_date} • Price: {w.price} {w.quantity_limit ? `• Limit: ${w.quantity_limit}` : ''}</p>
                      <span className={`mt-2 inline-flex px-2 py-0.5 rounded text-xs border ${w.is_active ? 'bg-[#4ECDC4]/10 border-[#4ECDC4]/20 text-[#333333]' : 'bg-white border-[#E3E4E6] text-[#999999]'}`}>{w.is_active ? 'Active' : 'Inactive'}</span>
                    </div>
                    <button onClick={() => handleDeleteWindow(w.id)} className="text-xs font-medium text-[#FF6B6B] hover:text-[#D94545] border border-transparent hover:border-[#FF6B6B]/20 px-2 py-1 rounded">Delete</button>
                  </div>
                ))}
              </div>
            ) : (
              <div className="rounded-lg border border-dashed border-[#D1D2D4] bg-[#F7F8FA] p-6 text-center">
                <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-white border border-[#E3E4E6]">💰</div>
                <h3 className="text-sm font-semibold text-[#333333]">No pricing windows yet</h3>
                <p className="mt-1 text-xs text-[#999999] max-w-md mx-auto">Create windows to schedule price changes. Preview shows how buyers will see prices — now you can create them here.</p>
              </div>
            )}
            {!showForm ? (
              <button type="button" onClick={() => setShowForm(true)} className="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-lg border-2 border-dashed border-[#FF6B6B]/30 bg-white text-sm font-semibold text-[#FF6B6B] hover:bg-[#FF6B6B] hover:text-white hover:border-[#FF6B6B] transition-colors">+ Add Pricing Window</button>
            ) : (
              <form onSubmit={handleCreateWindow} className="border border-[#E3E4E6] rounded-xl p-6 bg-white shadow-sm space-y-4">
                <h3 className="text-sm font-semibold text-[#333333]">New Pricing Window</h3>
                <div className="grid md:grid-cols-2 gap-4">
                  <div className="md:col-span-2">
                    <label className="block text-xs font-medium text-[#333333] mb-1">Window Name *</label>
                    <input value={formWindow.window_name} onChange={(e) => setFormWindow((p) => ({ ...p, window_name: e.target.value }))} placeholder="Early Bird" className="w-full rounded-lg border border-[#D1D2D4] bg-white py-2 px-3 text-sm focus:outline-none focus:border-[#FF6B6B]" required />
                  </div>
                  <div>
                    <label className="block text-xs font-medium text-[#333333] mb-1">Ticket Tier *</label>
                    <select value={formWindow.ticket_category_id} onChange={(e) => setFormWindow((p) => ({ ...p, ticket_category_id: e.target.value }))} className="w-full rounded-lg border border-[#D1D2D4] bg-white py-2 px-3 text-sm focus:outline-none focus:border-[#FF6B6B]" required>
                      <option value="">Select tier</option>
                      {tiers.map((t) => (
                        <option key={t.id} value={t.id}>{t.name} — {t.price} NGN</option>
                      ))}
                    </select>
                  </div>
                  <div>
                    <label className="block text-xs font-medium text-[#333333] mb-1">Price *</label>
                    <input type="number" step="0.01" value={formWindow.price} onChange={(e) => setFormWindow((p) => ({ ...p, price: e.target.value }))} placeholder="1999" className="w-full rounded-lg border border-[#D1D2D4] bg-white py-2 px-3 text-sm focus:outline-none focus:border-[#FF6B6B]" required />
                  </div>
                  <div>
                    <label className="block text-xs font-medium text-[#333333] mb-1">Start *</label>
                    <input type="datetime-local" value={formWindow.start_date_time} onChange={(e) => setFormWindow((p) => ({ ...p, start_date_time: e.target.value }))} className="w-full rounded-lg border border-[#D1D2D4] bg-white py-2 px-3 text-sm" required />
                  </div>
                  <div>
                    <label className="block text-xs font-medium text-[#333333] mb-1">End *</label>
                    <input type="datetime-local" value={formWindow.end_date_time} onChange={(e) => setFormWindow((p) => ({ ...p, end_date_time: e.target.value }))} className="w-full rounded-lg border border-[#D1D2D4] bg-white py-2 px-3 text-sm" required />
                  </div>
                  <div>
                    <label className="block text-xs font-medium text-[#333333] mb-1">Quantity Limit</label>
                    <input type="number" value={formWindow.quantity_limit} onChange={(e) => setFormWindow((p) => ({ ...p, quantity_limit: e.target.value }))} placeholder="Optional" className="w-full rounded-lg border border-[#D1D2D4] bg-white py-2 px-3 text-sm" />
                  </div>
                  <div className="flex items-center gap-2 pt-6">
                    <input type="checkbox" checked={formWindow.is_active} onChange={(e) => setFormWindow((p) => ({ ...p, is_active: e.target.checked }))} className="h-4 w-4 rounded border-[#D1D2D4] text-[#FF6B6B] focus:ring-[#FF6B6B]" />
                    <span className="text-sm text-[#333333]">Active</span>
                  </div>
                </div>
                <div className="flex gap-3">
                  <button type="submit" disabled={savingWindow} className="flex-1 inline-flex items-center justify-center px-4 py-2 rounded-lg bg-[#FF6B6B] text-white text-sm font-semibold hover:bg-[#D94545] disabled:opacity-50">{savingWindow ? 'Saving...' : 'Create Window'}</button>
                  <button type="button" onClick={() => setShowForm(false)} className="px-4 py-2 rounded-lg border border-[#D1D2D4] bg-white text-sm font-medium hover:bg-[#F7F8FA]">Cancel</button>
                </div>
              </form>
            )}
          </div>

          <div className="mt-6 flex justify-between items-center pt-6 border-t border-[#E3E4E6]">
            <p className="text-xs text-[#B3B3B3]">Deep link: /organizer/events/{eventId}/pricing</p>
            <button onClick={() => setShowPreviewModal(true)} className="text-sm font-medium text-[#FF6B6B] hover:text-[#D94545]">
              Preview →
            </button>
          </div>
        </div>
      </div>

      {showPreviewModal && (
        <PricingPreviewModal
          eventId={eventId}
          onClose={() => setShowPreviewModal(false)}
        />
      )}
    </div>
  );
};

export default EventPricingConfigPage;
