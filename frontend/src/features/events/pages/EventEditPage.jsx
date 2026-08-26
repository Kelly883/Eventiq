import React, { useState, useEffect, useCallback } from 'react';
import { Link, useParams, useNavigate } from 'react-router-dom';
import { api, showToast } from '../../../lib/api';
import Skeleton from '../../../components/Skeleton';

/**
 * Event Edit Page — form for editing an existing event.
 * Identical to EventCreatePage but loads existing event via eventId from URL,
 * pre-fills all fields, shows preview of current banner, and handles Save/Archive.
 * Uses same card layout (Details, Venue, Tickets, Media) for consistency.
 */
const EventEditPage = () => {
  const navigate = useNavigate();
  const { eventId } = useParams();

  const [form, setForm] = useState({
    title: '',
    description: '',
    category: 'concert',
    startDate: '',
    endDate: '',
    startTime: '',
    endTime: '',
    venueName: '',
    venueAddress: '',
    capacity: '',
    bannerUrl: '',
    isPublic: true,
  });

  const [tiers, setTiers] = useState([{ name: 'Regular', price: '', quantity: '' }]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [saving, setSaving] = useState(false);
  const [errors, setErrors] = useState({});

  const handleInputChange = useCallback((e) => {
    const { name, value, type, checked } = e.target;
    setForm((prev) => ({ ...prev, [name]: type === 'checkbox' ? checked : value }));
    if (errors[name]) setErrors((p) => ({ ...p, [name]: undefined }));
  }, [errors]);

  const addTier = useCallback(() => {
    if (tiers.length >= 10) {
      showToast('Limit reached', 'Maximum 10 tiers per event.', 'warning');
      return;
    }
    setTiers((prev) => [...prev, { name: '', price: '', quantity: '' }]);
  }, [tiers.length]);

  const removeTier = useCallback((idx) => {
    if (tiers.length <= 1) return;
    setTiers((prev) => prev.filter((_, i) => i !== idx));
  }, [tiers.length]);

  const updateTier = useCallback((idx, field, value) => {
    setTiers((prev) => prev.map((t, i) => (i === idx ? { ...t, [field]: value } : t)));
  }, []);

  const validateForm = useCallback(() => {
    const n = {};
    if (!form.title.trim()) n.title = 'Event title is required';
    if (!form.startDate) n.startDate = 'Start date is required';
    if (form.capacity && Number(form.capacity) <= 0) n.capacity = 'Capacity must be > 0';
    tiers.forEach((t, i) => {
      if (!t.name.trim()) n[`tierName-${i}`] = 'Tier name required';
    });
    setErrors(n);
    return Object.keys(n).length === 0;
  }, [form, tiers]);

  const handleSaveChanges = useCallback(async (e) => {
    e.preventDefault();
    if (!validateForm()) return;
    setSaving(true);
    try {
      await api.put(`/organizer/events/${eventId}`, {
        title: form.title,
        description: form.description,
        category: form.category,
        startDate: form.startDate,
        startTime: form.startTime,
        endDate: form.endDate || form.startDate,
        endTime: form.endTime || form.startTime,
        venueName: form.venueName,
        venueAddress: form.venueAddress,
        capacity: form.capacity ? Number(form.capacity) : null,
        isPublic: form.isPublic,
        ticketTiers: tiers.map((t) => ({
          name: t.name,
          price: t.price === '' ? 0 : Number(t.price),
          quantity: t.quantity === '' ? 0 : Number(t.quantity),
        })),
        bannerUrl: form.bannerUrl,
      });
      showToast('Changes saved', 'Event updated successfully!', 'success');
      navigate('/organizer/events');
    } catch (err) {
      const msg = err?.response?.data?.message || 'Failed to save changes';
      const status = err?.response?.status;
      if (status === 404) setError('Event not found');
      else showToast('Save failed', msg, 'error');
    } finally {
      setSaving(false);
    }
  }, [validateForm, form, tiers, eventId, navigate]);

  const handleArchive = useCallback(async () => {
    if (!window.confirm('Archive this event? This will hide it from listings.')) return;
    setSaving(true);
    try {
      await api.delete(`/organizer/events/${eventId}`);
      showToast('Event archived', 'Event has been archived.', 'success');
      navigate('/organizer/events');
    } catch (err) {
      showToast('Archive failed', err?.response?.data?.message || 'Could not archive event', 'error');
    } finally {
      setSaving(false);
    }
  }, [eventId, navigate]);

  useEffect(() => {
    let cancelled = false;
    async function fetchEvent() {
      if (!eventId) {
        setError('Invalid event ID');
        setLoading(false);
        return;
      }
      try {
        const res = await api.get(`/organizer/events/${eventId}`);
        if (cancelled) return;
        const data = res.data?.event || res.data?.data || res.data;
        if (!data || !data.id) {
          setError('Event not found');
          return;
        }
        setForm({
          title: data.title || '',
          description: data.description || '',
          category: data.category || 'concert',
          startDate: data.start_date || data.startDate || '',
          endDate: data.end_date || data.endDate || '',
          startTime: data.start_time || data.startTime || '',
          endTime: data.end_time || data.endTime || '',
          venueName: data.venue_name || data.venueName || '',
          venueAddress: data.venue_address || data.venueAddress || '',
          capacity: data.capacity ? String(data.capacity) : '',
          bannerUrl: data.banner_url || data.bannerUrl || '',
          isPublic: data.is_public ?? data.isPublic ?? true,
        });
        const apiTiers = data.ticket_tiers || data.ticketTiers || data.tiers;
        if (Array.isArray(apiTiers) && apiTiers.length) {
          setTiers(apiTiers.map((t) => ({
            name: t.name || '',
            price: t.price != null ? String(t.price) : '',
            quantity: t.quantity != null ? String(t.quantity) : t.available != null ? String(t.available) : '',
          })));
        }
      } catch (err) {
        if (cancelled) return;
        const status = err?.response?.status;
        if (status === 404) setError('Event not found');
        else if (status === 403) setError('You do not own this event');
        else setError(err?.response?.data?.message || 'Failed to load event');
      } finally {
        if (!cancelled) setLoading(false);
      }
    }
    fetchEvent();
    return () => { cancelled = true; };
  }, [eventId]);

  if (loading) {
    return (
      <div className="min-h-screen bg-[#F7F8FA] p-6 md:p-10">
        <div className="mx-auto max-w-3xl space-y-6">
          <Skeleton variant="card" count={1} />
          <Skeleton variant="card" count={1} />
          <Skeleton variant="card" count={1} />
        </div>
      </div>
    );
  }

  if (error) {
    const is404 = error.toLowerCase().includes('not found');
    return (
      <div className="min-h-screen bg-[#F7F8FA] p-6 md:p-10">
        <div className="mx-auto max-w-xl text-center bg-white rounded-xl border border-[#E3E4E6] p-10 shadow-sm">
          <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-[#F7F8FA] border border-[#E3E4E6] text-2xl">
            {is404 ? '🔍' : '⚠️'}
          </div>
          <h2 className="text-2xl font-bold text-[#333333]">{is404 ? 'Event Not Found' : 'Unable to load event'}</h2>
          <p className="mt-2 text-sm text-[#999999]">{error}</p>
          <p className="mt-1 text-xs text-[#B3B3B3]">Event ID: {eventId}</p>
          <div className="mt-6 flex justify-center gap-3">
            <Link to="/organizer/events" className="inline-flex px-4 py-2 rounded-lg bg-[#FF6B6B] text-white text-sm font-semibold hover:bg-[#D94545]">← Back to Events</Link>
            <button type="button" onClick={() => window.location.reload()} className="px-4 py-2 rounded-lg border border-[#D1D2D4] bg-white text-sm font-medium text-[#333333] hover:bg-[#F7F8FA]">Retry</button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-[#F7F8FA] p-6 md:p-10 pb-28">
      <div className="mx-auto max-w-3xl">
        <div className="mb-6">
          <Link to="/organizer/events" className="inline-flex items-center gap-2 text-sm font-medium text-[#999999] hover:text-[#333333] mb-3">
            ← Back to Events
          </Link>
          <h1 className="text-3xl font-bold text-[#333333] tracking-tight" style={{ fontFamily: 'Inter, system-ui, sans-serif' }}>
            Editing: {form.title || 'Event'}
          </h1>
          <p className="mt-1 text-sm text-[#999999]">Update details for event #{eventId}</p>
        </div>

        <form onSubmit={handleSaveChanges} noValidate>
          {/* Basic Info */}
          <div className="bg-white rounded-xl border border-[#E3E4E6] p-6 md:p-8 shadow-sm mb-6">
            <h2 className="text-xl font-semibold text-[#333333] mb-1">Basic Information</h2>
            <p className="text-sm text-[#999999] mb-6">Editing core details</p>
            <div className="grid md:grid-cols-2 gap-4">
              <div className="md:col-span-2">
                <label className="block text-sm font-medium text-[#333333] mb-1">Event Title *</label>
                <input name="title" value={form.title} onChange={handleInputChange} disabled={saving} className={`w-full rounded-lg border bg-white py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#FF6B6B]/20 focus:border-[#FF6B6B] ${errors.title ? 'border-[#FF6B6B]' : 'border-[#D1D2D4]'}`} required />
                {errors.title && <p className="mt-1 text-xs text-[#FF6B6B]">{errors.title}</p>}
              </div>
              <div>
                <label className="block text-sm font-medium text-[#333333] mb-1">Category</label>
                <select name="category" value={form.category} onChange={handleInputChange} disabled={saving} className="w-full rounded-lg border border-[#D1D2D4] bg-white py-2.5 px-3 text-sm focus:outline-none focus:border-[#FF6B6B] focus:ring-2 focus:ring-[#FF6B6B]/20">
                  <option value="concert">Concert</option>
                  <option value="festival">Festival</option>
                  <option value="workshop">Workshop</option>
                  <option value="networking">Networking</option>
                  <option value="conference">Conference</option>
                </select>
              </div>
              <div className="flex items-center gap-2 pt-6">
                <input type="checkbox" name="isPublic" checked={form.isPublic} onChange={handleInputChange} disabled={saving} className="h-4 w-4 rounded border-[#D1D2D4] text-[#FF6B6B] focus:ring-[#FF6B6B]" />
                <span className="text-sm font-medium text-[#333333]">Public Event</span>
              </div>
              <div>
                <label className="block text-sm font-medium text-[#333333] mb-1">Start Date *</label>
                <input name="startDate" type="date" value={form.startDate} onChange={handleInputChange} disabled={saving} className={`w-full rounded-lg border bg-white py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#FF6B6B]/20 ${errors.startDate ? 'border-[#FF6B6B]' : 'border-[#D1D2D4]'}`} />
                {errors.startDate && <p className="mt-1 text-xs text-[#FF6B6B]">{errors.startDate}</p>}
              </div>
              <div>
                <label className="block text-sm font-medium text-[#333333] mb-1">Start Time</label>
                <input name="startTime" type="time" value={form.startTime} onChange={handleInputChange} disabled={saving} className="w-full rounded-lg border border-[#D1D2D4] bg-white py-2.5 px-3 text-sm focus:outline-none focus:border-[#FF6B6B]" />
              </div>
              <div>
                <label className="block text-sm font-medium text-[#333333] mb-1">End Date</label>
                <input name="endDate" type="date" value={form.endDate} onChange={handleInputChange} disabled={saving} className="w-full rounded-lg border border-[#D1D2D4] bg-white py-2.5 px-3 text-sm focus:outline-none focus:border-[#FF6B6B]" />
              </div>
              <div>
                <label className="block text-sm font-medium text-[#333333] mb-1">End Time</label>
                <input name="endTime" type="time" value={form.endTime} onChange={handleInputChange} disabled={saving} className="w-full rounded-lg border border-[#D1D2D4] bg-white py-2.5 px-3 text-sm focus:outline-none focus:border-[#FF6B6B]" />
              </div>
              <div className="md:col-span-2">
                <label className="block text-sm font-medium text-[#333333] mb-1">Description</label>
                <textarea name="description" value={form.description} onChange={handleInputChange} disabled={saving} rows={4} placeholder="Describe your event..." className="w-full rounded-lg border border-[#D1D2D4] bg-white py-2.5 px-3 text-sm focus:outline-none focus:border-[#FF6B6B] focus:ring-2 focus:ring-[#FF6B6B]/20" />
              </div>
            </div>
          </div>

          {/* Venue */}
          <div className="bg-white rounded-xl border border-[#E3E4E6] p-6 md:p-8 shadow-sm mb-6">
            <h2 className="text-xl font-semibold text-[#333333] mb-1">Venue</h2>
            <p className="text-sm text-[#999999] mb-6">Where will it take place?</p>
            <div className="grid md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-[#333333] mb-1">Venue Name</label>
                <input name="venueName" value={form.venueName} onChange={handleInputChange} disabled={saving} className="w-full rounded-lg border border-[#D1D2D4] bg-white py-2.5 px-3 text-sm focus:outline-none focus:border-[#FF6B6B]" />
              </div>
              <div>
                <label className="block text-sm font-medium text-[#333333] mb-1">Capacity</label>
                <input name="capacity" type="number" value={form.capacity} onChange={handleInputChange} disabled={saving} className="w-full rounded-lg border border-[#D1D2D4] bg-white py-2.5 px-3 text-sm focus:outline-none focus:border-[#FF6B6B]" />
              </div>
              <div className="md:col-span-2">
                <label className="block text-sm font-medium text-[#333333] mb-1">Venue Address</label>
                <input name="venueAddress" value={form.venueAddress} onChange={handleInputChange} disabled={saving} className="w-full rounded-lg border border-[#D1D2D4] bg-white py-2.5 px-3 text-sm focus:outline-none focus:border-[#FF6B6B]" />
              </div>
            </div>
          </div>

          {/* Tickets */}
          <div className="bg-white rounded-xl border border-[#E3E4E6] p-6 md:p-8 shadow-sm mb-6">
            <div className="flex items-center justify-between mb-4">
              <h2 className="text-xl font-semibold text-[#333333]">Tickets</h2>
              <span className="text-xs font-medium text-[#999999] bg-[#F7F8FA] border border-[#E3E4E6] px-2 py-1 rounded-full">{tiers.length}/10</span>
            </div>
            <div className="space-y-3">
              {tiers.map((tier, i) => (
                <div key={i} className="grid grid-cols-1 md:grid-cols-12 gap-3 p-4 bg-[#F7F8FA] rounded-lg border border-[#E3E4E6]">
                  <div className="md:col-span-5">
                    <label className="block text-xs font-medium text-[#333333] mb-1">Tier Name *</label>
                    <input value={tier.name} onChange={(e) => updateTier(i, 'name', e.target.value)} disabled={saving} placeholder="Regular, VIP" className={`w-full rounded-lg border bg-white py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#FF6B6B]/20 ${errors[`tierName-${i}`] ? 'border-[#FF6B6B]' : 'border-[#D1D2D4]'}`} />
                    {errors[`tierName-${i}`] && <p className="mt-1 text-xs text-[#FF6B6B]">{errors[`tierName-${i}`]}</p>}
                  </div>
                  <div className="md:col-span-3">
                    <label className="block text-xs font-medium text-[#333333] mb-1">Price</label>
                    <input type="number" value={tier.price} onChange={(e) => updateTier(i, 'price', e.target.value)} disabled={saving} placeholder="25.00" className="w-full rounded-lg border border-[#D1D2D4] bg-white py-2 px-3 text-sm focus:outline-none focus:border-[#FF6B6B]" />
                  </div>
                  <div className="md:col-span-3">
                    <label className="block text-xs font-medium text-[#333333] mb-1">Quantity</label>
                    <input type="number" value={tier.quantity} onChange={(e) => updateTier(i, 'quantity', e.target.value)} disabled={saving} placeholder="100" className="w-full rounded-lg border border-[#D1D2D4] bg-white py-2 px-3 text-sm focus:outline-none focus:border-[#FF6B6B]" />
                  </div>
                  <div className="md:col-span-1 flex items-end">
                    <button type="button" onClick={() => removeTier(i)} disabled={saving || tiers.length <= 1} className="w-full rounded-lg border border-[#E3E4E6] bg-white py-2 text-xs font-medium text-[#FF6B6B] hover:bg-[#FF6B6B] hover:text-white disabled:opacity-40">Remove</button>
                  </div>
                </div>
              ))}
            </div>
            <button type="button" onClick={addTier} disabled={saving} className="mt-4 inline-flex px-4 py-2 rounded-lg text-sm font-medium text-[#FF6B6B] border border-[#FF6B6B] hover:bg-[#FF6B6B] hover:text-white disabled:opacity-50">+ Add Tier</button>
          </div>

          {/* Media */}
          <div className="bg-white rounded-xl border border-[#E3E4E6] p-6 md:p-8 shadow-sm mb-6">
            <h2 className="text-xl font-semibold text-[#333333] mb-1">Media</h2>
            {form.bannerUrl ? (
              <div className="mb-4">
                <img src={form.bannerUrl} alt={`${form.title} banner`} className="w-full rounded-lg h-48 object-cover border border-[#E3E4E6]" />
                <p className="mt-2 text-xs text-[#999999]">Current banner — upload a new file to replace it</p>
              </div>
            ) : <p className="text-sm text-[#999999] mb-4">No banner yet — add one below</p>}
            <label className="flex flex-col items-center justify-center w-full border-2 border-dashed rounded-xl p-8 text-center hover:border-[#FF6B6B] hover:bg-[#FF6B6B]/5 cursor-pointer group">
              <span className="text-2xl mb-2">📷</span>
              <span className="text-sm font-medium text-[#333333]">Click to upload banner</span>
              <span className="text-xs text-[#999999]">1200×628 JPG/PNG</span>
              <input type="file" accept="image/*" className="hidden" onChange={(e) => {
                const f = e.target.files?.[0];
                if (f) {
                  const r = new FileReader();
                  r.onload = () => setForm((p) => ({ ...p, bannerUrl: r.result }));
                  r.readAsDataURL(f);
                }
              }} />
            </label>
          </div>

          {/* Sticky footer */}
          <div className="fixed bottom-0 left-0 right-0 bg-white border-t border-[#E3E4E6] shadow-[0_-4px_12px_rgba(0,0,0,0.05)]">
            <div className="mx-auto max-w-3xl px-6 py-4 flex gap-3">
              <Link to="/organizer/events" className="flex-1 inline-flex items-center justify-center px-4 py-3 rounded-lg text-sm font-semibold bg-white text-[#333333] border border-[#D1D2D4] hover:bg-[#F7F8FA] text-center">
                Cancel
              </Link>
              <button type="submit" disabled={saving} className="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 rounded-lg text-sm font-semibold bg-[#FF6B6B] text-white hover:bg-[#D94545] disabled:opacity-50 shadow-sm">
                {saving ? <span className="h-4 w-4 border-2 border-white border-t-transparent rounded-full animate-spin" /> : null}
                {saving ? 'Saving...' : 'Save Changes'}
              </button>
              <button type="button" onClick={handleArchive} disabled={saving} className="inline-flex items-center justify-center px-4 py-3 rounded-lg text-sm font-semibold text-[#FF6B6B] border border-[#FF6B6B] hover:bg-[#FF6B6B] hover:text-white disabled:opacity-50">
                Archive
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  );
};

export default EventEditPage;
