import React, { useState, useEffect, useCallback, useRef } from 'react';
import { Link, useParams, useNavigate, useBlocker } from 'react-router-dom';
import { api, showToast } from '../../../lib/api';
import Skeleton from '../../../components/Skeleton';
import { ticketingService } from '../services/ticketingService';

function useSafeBlocker(shouldBlock) {
  try {
    return useBlocker(shouldBlock);
  } catch {
    return { state: 'unblocked', proceed: () => {}, reset: () => {} };
  }
}

/**
 * Ticket Tier Management — /organizer/events/:eventId/ticketing
 * Loads event + tiers, allows inline editing of tiers, tracks dirty state,
 * saves via PUT /organizer/events/:eventId/ticketing, shows success toast + refresh,
 * and blocks navigation when unsaved changes exist (beforeunload + useBlocker).
 */
const TicketTierManagementPage = () => {
  const { eventId, tierId } = useParams();
  const navigate = useNavigate();

  const [event, setEvent] = useState(null);
  const [tiers, setTiers] = useState([]);
  const [initialTiers, setInitialTiers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [saving, setSaving] = useState(false);
  const [fieldErrors, setFieldErrors] = useState({});

  const fetchData = useCallback(async () => {
    if (!eventId) {
      setError('Missing event ID');
      setLoading(false);
      return;
    }
    setLoading(true);
    setError(null);
    try {
      const { event: ev, tiers: t } = await ticketingService.getTicketTiers(eventId);
      if (!ev || !ev.id) {
        // fallback: try direct event fetch shape
        setError('Event not found');
        setLoading(false);
        return;
      }
      setEvent(ev);
      // Normalize tiers: ensure at least one if empty for UX
      const normalized = t.length ? t.map((tier) => ({
        id: tier.id,
        name: tier.name || '',
        description: tier.description || '',
        price: tier.price != null ? String(tier.price) : '',
        quantity: tier.quantity != null ? String(tier.quantity) : tier.available_count != null ? String(tier.available_count) : '',
        is_active: tier.is_active ?? true,
        tier_order: tier.tier_order ?? 0,
        status: tier.status || 'published',
        currency: tier.currency || 'NGN',
        sales_start_date: tier.sales_start_date || '',
        sales_end_date: tier.sales_end_date || '',
      })) : [];
      setTiers(normalized);
      setInitialTiers(JSON.parse(JSON.stringify(normalized)));
    } catch (err) {
      const status = err?.response?.status;
      if (status === 404) setError('Event not found');
      else if (status === 403) setError('You do not have access to this event');
      else setError(err?.response?.data?.message || 'Failed to load ticket tiers');
    } finally {
      setLoading(false);
    }
  }, [eventId]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  // Dirty detection: compare JSON stringified tiers
  const isDirty = JSON.stringify(tiers) !== JSON.stringify(initialTiers);

  // beforeunload for browser tab close/refresh
  useEffect(() => {
    if (!isDirty) return;
    const handler = (e) => {
      e.preventDefault();
      e.returnValue = '';
    };
    window.addEventListener('beforeunload', handler);
    return () => window.removeEventListener('beforeunload', handler);
  }, [isDirty]);

  // SPA navigation blocker — safe for BrowserRouter (see helper above)
  const blocker = useSafeBlocker(isDirty && !saving);
  useEffect(() => {
    if (blocker.state === 'blocked' && isDirty) {
      const ok = window.confirm('You have unsaved changes. Leave without saving?');
      if (ok) blocker.proceed();
      else blocker.reset();
    }
  }, [blocker, isDirty]);

  // Fallback for BrowserRouter (App.jsx:172 uses BrowserRouter, not data router) — useBlocker throws there,
  // so also intercept clicks + popstate manually when blocker is no-op.
  useEffect(() => {
    if (!isDirty || saving) return;
    // If useBlocker is active (data router), let it handle; otherwise manual
    // We detect data router by checking blocker has proceed; if useBlocker threw, it would not have been set,
    // but in BrowserRouter it throws, so we catch via window error? Instead just always add manual as backup:
    const handleClick = (e) => {
      // Only intercept if blocker is not in blocked state (means useBlocker not handling)
      if (blocker.state === 'blocked') return;
      const a = e.target.closest('a');
      if (!a) return;
      const href = a.getAttribute('href');
      if (!href || href.startsWith('#') || a.target === '_blank' || href.startsWith('http')) return;
      if (!href.startsWith('/')) return;
      if (!window.confirm('You have unsaved changes. Leave without saving?')) {
        e.preventDefault();
        e.stopPropagation();
      }
    };
    const handlePopState = () => {
      if (isDirty && !window.confirm('You have unsaved changes. Leave without saving?')) {
        window.history.pushState(null, '', window.location.href);
      }
    };
    document.addEventListener('click', handleClick, true);
    window.addEventListener('popstate', handlePopState);
    return () => {
      document.removeEventListener('click', handleClick, true);
      window.removeEventListener('popstate', handlePopState);
    };
  }, [isDirty, saving, blocker.state]);

  const updateTier = useCallback((idx, field, value) => {
    setTiers((prev) => prev.map((t, i) => (i === idx ? { ...t, [field]: value } : t)));
    // clear per-field error as user types
    setFieldErrors((prev) => {
      const copy = { ...prev };
      delete copy[`${field}-${idx}`];
      // map price -> price, name -> name, description -> description
      return copy;
    });
  }, []);

  const addTier = useCallback(() => {
    if (tiers.length >= 20) {
      showToast('Limit reached', 'Maximum 20 tiers per event.', 'warning');
      return;
    }
    setTiers((prev) => [...prev, {
      name: '',
      description: '',
      price: '',
      quantity: '',
      is_active: true,
      status: 'draft',
      currency: 'NGN',
      tier_order: prev.length,
    }]);
  }, [tiers.length]);

  const removeTier = useCallback((idx) => {
    if (!window.confirm('Delete this tier? This cannot be undone.')) return;
    setTiers((prev) => prev.filter((_, i) => i !== idx));
  }, []);

  const handleSave = useCallback(async () => {
    // Explicit validation — mirrors UpdateTicketTiersRequest.php rules (name/description/price required)
    const nextErrors = {};
    tiers.forEach((t, i) => {
      if (!t.name.trim()) nextErrors[`name-${i}`] = 'Name is required';
      if (!t.description.trim()) nextErrors[`description-${i}`] = 'Description is required (max 2000 chars)';
      if (t.description && t.description.length > 2000) nextErrors[`description-${i}`] = 'Max 2000 characters';
      if (t.price === '' || Number(t.price) < 0.01) nextErrors[`price-${i}`] = 'Price ≥ 0.01 required';
    });
    setFieldErrors(nextErrors);
    if (Object.keys(nextErrors).length) {
      const first = Object.values(nextErrors)[0];
      showToast('Validation failed', first, 'error');
      return;
    }
    setSaving(true);
    try {
      await ticketingService.updateTicketTiers(eventId, tiers);
      showToast('Ticket tiers saved', 'Ticket tiers updated successfully!', 'success');
      // Refresh list from backend to get IDs/normalized data
      await fetchData();
    } catch (err) {
      const msg = err?.response?.data?.message || err.message || 'Failed to save tiers';
      // Show validation errors inline if 422
      if (err?.response?.status === 422 && err?.response?.data?.errors) {
        showToast('Validation error', JSON.stringify(err.response.data.errors), 'error');
      } else {
        showToast('Save failed', msg, 'error');
      }
    } finally {
      setSaving(false);
    }
  }, [tiers, eventId, fetchData]);

  if (loading) {
    return (
      <div className="min-h-screen bg-[#F7F8FA] p-6 md:p-10">
        <div className="mx-auto max-w-4xl space-y-6">
          <Skeleton variant="card" count={1} />
          <Skeleton variant="table" count={4} />
        </div>
      </div>
    );
  }

  if (error) {
    const is404 = error.toLowerCase().includes('not found');
    return (
      <div className="min-h-screen bg-[#F7F8FA] p-6 md:p-10">
        <div className="mx-auto max-w-2xl text-center bg-white rounded-xl border border-[#E3E4E6] p-10 shadow-sm">
          <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-[#F7F8FA] border border-[#E3E4E6] text-2xl">
            {is404 ? '🔍' : '⚠️'}
          </div>
          <h2 className="text-2xl font-bold text-[#333333]">{is404 ? 'Event Not Found' : 'Unable to load tiers'}</h2>
          <p className="mt-2 text-sm text-[#999999]">{error}</p>
          <p className="mt-1 text-xs text-[#B3B3B3]">Event ID: {eventId}</p>
          <div className="mt-6 flex justify-center gap-3">
            <Link to="/organizer/events" className="inline-flex px-4 py-2 rounded-lg bg-[#FF6B6B] text-white text-sm font-semibold hover:bg-[#D94545]">← Back to Events</Link>
            <button type="button" onClick={fetchData} className="px-4 py-2 rounded-lg border border-[#D1D2D4] bg-white text-sm font-medium text-[#333333] hover:bg-[#F7F8FA]">Retry</button>
          </div>
        </div>
      </div>
    );
  }

  const focusedTierId = tierId ? String(tierId) : null;

  return (
    <div className="min-h-screen bg-[#F7F8FA] p-6 md:p-10 pb-24">
      <div className="mx-auto max-w-4xl">
        {/* Header */}
        <div className="mb-6">
          <Link to="/organizer/events" className="inline-flex items-center gap-2 text-sm font-medium text-[#999999] hover:text-[#333333] mb-3">
            ← Back to Events
          </Link>
          <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
              <h1 className="text-3xl font-bold text-[#333333] tracking-tight" style={{ fontFamily: 'Inter, system-ui, sans-serif' }}>
                Ticket Tiers
              </h1>
              <p className="mt-1 text-sm text-[#999999]">
                {event ? (
                  <>Managing tiers for <span className="font-semibold text-[#333333]">{event.title || `Event #${eventId}`}</span></>
                ) : (
                  `Event #${eventId}`
                )}
                {tiers.length ? ` — ${tiers.length} tier${tiers.length === 1 ? '' : 's'}` : ''}
              </p>
            </div>
            <div className="flex gap-2">
              <Link
                to={`/organizer/events/${eventId}/edit`}
                className="inline-flex items-center justify-center px-4 py-2 rounded-lg border border-[#D1D2D4] bg-white text-sm font-medium text-[#333333] hover:bg-[#F7F8FA]"
              >
                ✎ Edit Event
              </Link>
            </div>
          </div>
        </div>

        {/* Event management tabs — clarifies ticketing vs inventory vs pricing */}
        <div className="bg-white rounded-xl border border-[#E3E4E6] p-1.5 mb-6 shadow-sm flex flex-wrap gap-1">
          <Link
            to={`/organizer/events/${eventId}/ticketing`}
            className="px-4 py-2 rounded-lg text-sm font-semibold bg-[#FF6B6B] text-white shadow-sm"
            aria-current="page"
          >
            🎟️ Ticket Tiers
          </Link>
          <Link
            to={`/organizer/events/${eventId}/inventory`}
            className="px-4 py-2 rounded-lg text-sm font-medium text-[#333333] hover:bg-[#F7F8FA] border border-transparent"
          >
            📦 Inventory
          </Link>
          <Link
            to={`/organizer/events/${eventId}/pricing`}
            className="px-4 py-2 rounded-lg text-sm font-medium text-[#333333] hover:bg-[#F7F8FA] border border-transparent"
          >
            💰 Pricing
          </Link>
          <span className="ml-auto hidden md:inline-flex items-center text-xs text-[#999999] px-2">
            Tiers = what you sell • Inventory = how many / adjustments • Pricing = windows & rules
          </span>
        </div>

        {/* Event summary card */}
        {event && (
          <div className="bg-white rounded-xl border border-[#E3E4E6] p-4 mb-6 shadow-sm flex items-center justify-between">
            <div className="text-sm">
              <p className="font-semibold text-[#333333]">{event.title}</p>
              <p className="text-xs text-[#999999]">{event.venue_name || event.venueName || 'No venue'} • {event.start_date || event.startDate || ''}</p>
            </div>
            <span className={`px-2 py-1 rounded text-xs font-medium border ${event.status === 'draft' ? 'bg-[#FFDA6B]/30 text-[#333333] border-[#FFDA6B]' : 'bg-[#4ECDC4]/10 text-[#333333] border-[#4ECDC4]/20'}`}>
              {event.status || 'published'}
            </span>
          </div>
        )}

        {/* Tiers list */}
        <div className="bg-white rounded-xl border border-[#E3E4E6] shadow-sm p-6 md:p-8">
          {tiers.length === 0 ? (
            <div className="text-center py-10">
              <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-[#F7F8FA] border border-[#E3E4E6]">🎟️</div>
              <h3 className="text-sm font-semibold text-[#333333]">No ticket tiers yet</h3>
              <p className="mt-1 text-xs text-[#999999]">Add your first tier to start selling tickets.</p>
              <button type="button" onClick={addTier} className="mt-4 inline-flex px-4 py-2 rounded-lg bg-[#FF6B6B] text-white text-sm font-semibold hover:bg-[#D94545]">+ Add Tier</button>
            </div>
          ) : (
            <div className="space-y-4">
              {tiers.map((tier, idx) => {
                const isFocused = focusedTierId && String(tier.id) === focusedTierId;
                return (
                  <div
                    key={tier.id || `new-${idx}`}
                    id={`tier-${tier.id || idx}`}
                    className={`rounded-lg border p-4 ${isFocused ? 'border-[#FF6B6B] bg-[#FF6B6B]/5 shadow-sm' : 'border-[#E3E4E6] bg-[#F7F8FA]'} ${isFocused ? 'ring-1 ring-[#FF6B6B]/20' : ''}`}
                  >
                    <div className="flex items-center justify-between mb-3">
                      <h3 className="text-sm font-semibold text-[#333333] flex items-center gap-2">
                        Tier {idx + 1}
                        {isFocused && <span className="text-xs font-normal text-[#FF6B6B] bg-white border border-[#FF6B6B]/20 px-2 py-0.5 rounded-full">editing</span>}
                        {!tier.is_active && <span className="text-xs text-[#999999]">(inactive)</span>}
                      </h3>
                      <div className="flex gap-2">
                        <Link
                          to={`/organizer/events/${eventId}/ticketing/tier/${tier.id || idx}/edit`}
                          className="text-xs font-medium text-[#FF6B6B] hover:text-[#D94545] border border-transparent hover:border-[#E3E4E6] bg-white px-2 py-1 rounded"
                        >
                          Edit
                        </Link>
                        <button type="button" onClick={() => removeTier(idx)} className="text-xs font-medium text-[#FF6B6B] hover:bg-white border border-transparent hover:border-[#FF6B6B]/20 px-2 py-1 rounded">
                          Delete
                        </button>
                      </div>
                    </div>

                    <div className="grid md:grid-cols-2 gap-3">
                      <div>
                        <label className="block text-xs font-medium text-[#333333] mb-1">Name *</label>
                        <input
                          value={tier.name}
                          onChange={(e) => updateTier(idx, 'name', e.target.value)}
                          placeholder="e.g. Regular, VIP, Early Bird"
                          className={`w-full rounded-lg border bg-white py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#FF6B6B]/20 ${fieldErrors[`name-${idx}`] ? 'border-[#FF6B6B] focus:border-[#FF6B6B]' : 'border-[#D1D2D4] focus:border-[#FF6B6B]'}`}
                        />
                        {fieldErrors[`name-${idx}`] && <p className="mt-1 text-xs text-[#FF6B6B]">{fieldErrors[`name-${idx}`]}</p>}
                      </div>
                      <div>
                        <label className="block text-xs font-medium text-[#333333] mb-1">Price (NGN) *</label>
                        <input
                          type="number"
                          min="0"
                          step="0.01"
                          value={tier.price}
                          onChange={(e) => updateTier(idx, 'price', e.target.value)}
                          placeholder="2500"
                          className={`w-full rounded-lg border bg-white py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#FF6B6B]/20 ${fieldErrors[`price-${idx}`] ? 'border-[#FF6B6B] focus:border-[#FF6B6B]' : 'border-[#D1D2D4] focus:border-[#FF6B6B]'}`}
                        />
                        {fieldErrors[`price-${idx}`] && <p className="mt-1 text-xs text-[#FF6B6B]">{fieldErrors[`price-${idx}`]}</p>}
                      </div>
                      <div className="md:col-span-2">
                        <label className="block text-xs font-medium text-[#333333] mb-1">Description *</label>
                        <textarea
                          value={tier.description}
                          onChange={(e) => updateTier(idx, 'description', e.target.value)}
                          placeholder="What's included? Benefits, perks... (required, max 2000)"
                          rows={2}
                          className={`w-full rounded-lg border bg-white py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#FF6B6B]/20 ${fieldErrors[`description-${idx}`] ? 'border-[#FF6B6B] focus:border-[#FF6B6B]' : 'border-[#D1D2D4] focus:border-[#FF6B6B]'}`}
                        />
                        {fieldErrors[`description-${idx}`] ? (
                          <p className="mt-1 text-xs text-[#FF6B6B]">{fieldErrors[`description-${idx}`]}</p>
                        ) : (
                          <p className="mt-1 text-xs text-[#B3B3B3]">{tier.description.length}/2000</p>
                        )}
                      </div>
                      <div>
                        <label className="block text-xs font-medium text-[#333333] mb-1">Quantity (initial stock)</label>
                        <input
                          type="number"
                          min="1"
                          value={tier.quantity}
                          onChange={(e) => updateTier(idx, 'quantity', e.target.value)}
                          placeholder="100"
                          className="w-full rounded-lg border border-[#D1D2D4] bg-white py-2 px-3 text-sm focus:outline-none focus:border-[#FF6B6B]"
                        />
                        <p className="mt-1 text-xs text-[#999999]">
                          {tier.id ? 'Use Inventory tab to adjust sold/remaining stock' : 'Set initial available tickets'}
                        </p>
                      </div>
                      <div className="flex items-end gap-3">
                        <label className="flex items-center gap-2 text-sm text-[#333333]">
                          <input type="checkbox" checked={!!tier.is_active} onChange={(e) => updateTier(idx, 'is_active', e.target.checked)} className="h-4 w-4 rounded border-[#D1D2D4] text-[#FF6B6B] focus:ring-[#FF6B6B]" />
                          Active
                        </label>
                        <span className="text-xs text-[#B3B3B3]">Visible to buyers</span>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          )}

          <div className="mt-6 flex flex-wrap gap-3">
            <button
              type="button"
              onClick={addTier}
              className="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-[#FF6B6B] text-sm font-medium text-[#FF6B6B] hover:bg-[#FF6B6B] hover:text-white transition-colors"
            >
              + Add Tier
            </button>
            {tiers.length > 0 && (
              <button
                type="button"
                onClick={handleSave}
                disabled={saving || !isDirty}
                className="inline-flex items-center gap-2 px-6 py-2 rounded-lg bg-[#FF6B6B] text-white text-sm font-semibold hover:bg-[#D94545] disabled:opacity-50 disabled:cursor-not-allowed shadow-sm"
              >
                {saving ? (
                  <>
                    <span className="h-4 w-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                    Saving...
                  </>
                ) : (
                  'Save Changes'
                )}
              </button>
            )}
            {isDirty && !saving && (
              <span className="inline-flex items-center text-xs font-medium text-[#FF6B6B] bg-[#FF6B6B]/10 border border-[#FF6B6B]/20 px-3 py-2 rounded-lg">
                ● Unsaved changes
              </span>
            )}
          </div>
        </div>

        {/* Footer help */}
        <p className="mt-4 text-center text-xs text-[#999999]">
          Changes affect only future purchases. Already sold tickets are not modified.
        </p>
      </div>
    </div>
  );
};

export default TicketTierManagementPage;
