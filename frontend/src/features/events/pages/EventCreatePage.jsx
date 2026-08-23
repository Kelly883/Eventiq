import React, { useState, useCallback, useRef } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { api, showToast } from '../../../lib/api';

/**
 * Event Create Page — comprehensive form for creating a new event.
 * Broken into logical sections (Details, Venue, Tickets, Media) using cards (design-system.md).
 * Features dynamic ticket tiers, banner upload, Save Draft and Publish actions,
 * real-time validation on blur, sticky footer, and redirect on success.
 */
const EventCreatePage = () => {
  const navigate = useNavigate();
  const fileInputRef = useRef(null);

  const [form, setForm] = useState({
    title: '',
    description: '',
    category: 'concert',
    startDate: '',
    startTime: '',
    endDate: '',
    endTime: '',
    venueName: '',
    venueAddress: '',
    capacity: '',
    isPublic: true,
  });

  const [tiers, setTiers] = useState([
    { name: 'Regular', price: '', quantity: '' },
  ]);

  const [bannerFile, setBannerFile] = useState(null);
  const [bannerPreview, setBannerPreview] = useState('');
  const [errors, setErrors] = useState({});
  const [savingDraft, setSavingDraft] = useState(false);
  const [publishing, setPublishing] = useState(false);

  const handleInputChange = useCallback((e) => {
    const { name, value, type, checked } = e.target;
    setForm((prev) => ({ ...prev, [name]: type === 'checkbox' ? checked : value }));
    if (errors[name]) setErrors((prev) => ({ ...prev, [name]: undefined }));
  }, [errors]);

  const validateField = useCallback((name, value) => {
    let msg;
    if (name === 'title' && !value.trim()) msg = 'Event title is required';
    if (name === 'startDate' && !value) msg = 'Start date is required';
    if (name === 'capacity' && value && Number(value) <= 0) msg = 'Capacity must be greater than 0';
    return msg;
  }, []);

  const handleBlur = useCallback((e) => {
    const { name, value } = e.target;
    const msg = validateField(name, value);
    if (msg) setErrors((prev) => ({ ...prev, [name]: msg }));
  }, [validateField]);

  const addTier = useCallback(() => {
    if (tiers.length >= 10) {
      showToast('Limit reached', 'Maximum 10 tiers per event.', 'warning');
      return;
    }
    setTiers((prev) => [...prev, { name: '', price: '', quantity: '' }]);
  }, [tiers.length]);

  const removeTier = useCallback((index) => {
    if (tiers.length <= 1) return;
    setTiers((prev) => prev.filter((_, i) => i !== index));
  }, [tiers.length]);

  const updateTier = useCallback((index, field, value) => {
    setTiers((prev) => prev.map((t, i) => (i === index ? { ...t, [field]: value } : t)));
  }, []);

  const handleBannerChange = useCallback((e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    setBannerFile(file);
    const reader = new FileReader();
    reader.onload = () => setBannerPreview(reader.result);
    reader.readAsDataURL(file);
  }, []);

  const validateForm = useCallback(() => {
    const newErrors = {};
    if (!form.title.trim()) newErrors.title = 'Event title is required';
    if (!form.startDate) newErrors.startDate = 'Start date is required';
    if (!form.startTime) newErrors.startTime = 'Start time is required';
    if (form.capacity && Number(form.capacity) <= 0) newErrors.capacity = 'Capacity must be greater than 0';
    tiers.forEach((t, i) => {
      if (!t.name.trim()) newErrors[`tierName-${i}`] = 'Tier name required';
      if (t.price !== '' && Number(t.price) < 0) newErrors[`tierPrice-${i}`] = 'Price cannot be negative';
      if (t.quantity !== '' && Number(t.quantity) <= 0) newErrors[`tierQuantity-${i}`] = 'Quantity must be > 0';
    });
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  }, [form, tiers]);

  const buildPayload = useCallback((status) => ({
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
    status,
    ticketTiers: tiers.map((t) => ({
      name: t.name,
      price: t.price === '' ? 0 : Number(t.price),
      quantity: t.quantity === '' ? 0 : Number(t.quantity),
    })),
  }), [form, tiers]);

  const uploadBannerIfNeeded = useCallback(async () => {
    if (!bannerFile) return null;
    const fd = new FormData();
    fd.append('banner', bannerFile);
    try {
      const res = await api.post('/organizer/events/banner', fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      return res.data?.url || res.data?.bannerUrl || null;
    } catch {
      // Fallback: endpoint may not exist yet, warn but continue
      showToast('Banner upload skipped', 'Banner will be uploaded separately.', 'warning');
      return null;
    }
  }, [bannerFile]);

  const handleSaveDraft = useCallback(async (e) => {
    e.preventDefault();
    if (!validateForm()) return;
    setSavingDraft(true);
    try {
      const bannerUrl = await uploadBannerIfNeeded();
      const payload = buildPayload('draft');
      if (bannerUrl) payload.bannerUrl = bannerUrl;
      await api.post('/organizer/events', payload);
      showToast('Event created', 'Event created successfully!', 'success');
      navigate('/organizer/events');
    } catch (err) {
      const msg = err?.response?.data?.message || 'Failed to save draft';
      showToast('Save failed', msg, 'error');
    } finally {
      setSavingDraft(false);
    }
  }, [validateForm, uploadBannerIfNeeded, buildPayload, navigate]);

  const handlePublish = useCallback(async (e) => {
    e.preventDefault();
    if (!validateForm()) return;
    setPublishing(true);
    try {
      const bannerUrl = await uploadBannerIfNeeded();
      const payload = buildPayload('published');
      if (bannerUrl) payload.bannerUrl = bannerUrl;
      await api.post('/organizer/events', payload);
      showToast('Event published', 'Event created successfully!', 'success');
      navigate('/organizer/events');
    } catch (err) {
      const msg = err?.response?.data?.message || 'Failed to publish event';
      showToast('Publish failed', msg, 'error');
    } finally {
      setPublishing(false);
    }
  }, [validateForm, uploadBannerIfNeeded, buildPayload, navigate]);

  const isSaving = savingDraft || publishing;

  return (
    <div className="min-h-screen bg-[#F7F8FA] p-6 md:p-10 pb-28">
      <div className="mx-auto max-w-3xl">
        {/* Header */}
        <div className="mb-8 text-center md:text-left">
          <Link to="/organizer/events" className="inline-flex items-center gap-2 text-sm font-medium text-[#999999] hover:text-[#333333] mb-3">
            ← Back to Events
          </Link>
          <h1 className="text-3xl font-bold text-[#333333] tracking-tight" style={{ fontFamily: 'Inter, system-ui, sans-serif', lineHeight: '1.2' }}>
            Create New Event
          </h1>
          <p className="mt-2 text-sm text-[#999999]">Fill in the details below to create your event.</p>
        </div>

        {/* Progress indicator */}
        <div className="bg-white rounded-xl p-4 shadow-sm mb-6 border border-[#E3E4E6] flex items-center gap-2 text-xs font-semibold">
          <span className="px-3 py-1 rounded-full bg-[#FF6B6B] text-white">Basic Info</span>
          <span className="text-[#D1D2D4]">—</span>
          <span className="px-3 py-1 rounded-full bg-[#F7F8FA] text-[#999999] border border-[#E3E4E6]">Venue</span>
          <span className="text-[#D1D2D4]">—</span>
          <span className="px-3 py-1 rounded-full bg-[#F7F8FA] text-[#999999] border border-[#E3E4E6]">Tickets</span>
          <span className="text-[#D1D2D4]">—</span>
          <span className="px-3 py-1 rounded-full bg-[#F7F8FA] text-[#999999] border border-[#E3E4E6]">Media</span>
        </div>

        <form onSubmit={handlePublish} noValidate>
          {/* Card: Basic Information */}
          <div className="bg-white rounded-xl border border-[#E3E4E6] p-6 md:p-8 shadow-sm mb-6">
            <h2 className="text-xl font-semibold text-[#333333] mb-1" style={{ fontWeight: 600, fontSize: '1.5rem', lineHeight: '1.3' }}>Basic Information</h2>
            <p className="text-sm text-[#999999] mb-6">Core event details</p>

            <div className="grid md:grid-cols-2 gap-4">
              <div className="md:col-span-2">
                <label className="block text-sm font-medium text-[#333333] mb-1">Event Title *</label>
                <input
                  name="title"
                  value={form.title}
                  onChange={handleInputChange}
                  onBlur={handleBlur}
                  disabled={isSaving}
                  placeholder="e.g. Summer Concert 2025"
                  className={`w-full rounded-lg border bg-white py-2.5 px-3 text-sm text-[#333333] placeholder:text-[#B3B3B3] focus:outline-none focus:ring-2 focus:ring-[#FF6B6B]/20 focus:border-[#FF6B6B] ${errors.title ? 'border-[#FF6B6B]' : 'border-[#D1D2D4]'}`}
                  required
                />
                {errors.title && <p className="mt-1 text-xs text-[#FF6B6B]">{errors.title}</p>}
              </div>

              <div>
                <label className="block text-sm font-medium text-[#333333] mb-1">Category</label>
                <select
                  name="category"
                  value={form.category}
                  onChange={handleInputChange}
                  disabled={isSaving}
                  className="w-full rounded-lg border border-[#D1D2D4] bg-white py-2.5 px-3 text-sm text-[#333333] focus:outline-none focus:border-[#FF6B6B] focus:ring-2 focus:ring-[#FF6B6B]/20"
                >
                  <option value="concert">Concert</option>
                  <option value="festival">Festival</option>
                  <option value="workshop">Workshop</option>
                  <option value="networking">Networking</option>
                  <option value="conference">Conference</option>
                </select>
              </div>

              <div className="flex items-center gap-3 pt-6">
                <label className="flex items-center gap-2 text-sm font-medium text-[#333333] cursor-pointer">
                  <input
                    type="checkbox"
                    name="isPublic"
                    checked={form.isPublic}
                    onChange={handleInputChange}
                    disabled={isSaving}
                    className="h-4 w-4 rounded border-[#D1D2D4] text-[#FF6B6B] focus:ring-[#FF6B6B]"
                  />
                  Public Event
                </label>
                <span className="text-xs text-[#999999]">Visible to everyone</span>
              </div>

              <div>
                <label className="block text-sm font-medium text-[#333333] mb-1">Start Date *</label>
                <input
                  name="startDate"
                  type="date"
                  value={form.startDate}
                  onChange={handleInputChange}
                  onBlur={handleBlur}
                  disabled={isSaving}
                  className={`w-full rounded-lg border bg-white py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#FF6B6B]/20 focus:border-[#FF6B6B] ${errors.startDate ? 'border-[#FF6B6B]' : 'border-[#D1D2D4]'}`}
                />
                {errors.startDate && <p className="mt-1 text-xs text-[#FF6B6B]">{errors.startDate}</p>}
              </div>

              <div>
                <label className="block text-sm font-medium text-[#333333] mb-1">Start Time *</label>
                <input
                  name="startTime"
                  type="time"
                  value={form.startTime}
                  onChange={handleInputChange}
                  onBlur={handleBlur}
                  disabled={isSaving}
                  className={`w-full rounded-lg border bg-white py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#FF6B6B]/20 focus:border-[#FF6B6B] ${errors.startTime ? 'border-[#FF6B6B]' : 'border-[#D1D2D4]'}`}
                />
                {errors.startTime && <p className="mt-1 text-xs text-[#FF6B6B]">{errors.startTime}</p>}
              </div>

              <div>
                <label className="block text-sm font-medium text-[#333333] mb-1">End Date</label>
                <input
                  name="endDate"
                  type="date"
                  value={form.endDate}
                  onChange={handleInputChange}
                  disabled={isSaving}
                  className="w-full rounded-lg border border-[#D1D2D4] bg-white py-2.5 px-3 text-sm focus:outline-none focus:border-[#FF6B6B] focus:ring-2 focus:ring-[#FF6B6B]/20"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-[#333333] mb-1">End Time</label>
                <input
                  name="endTime"
                  type="time"
                  value={form.endTime}
                  onChange={handleInputChange}
                  disabled={isSaving}
                  className="w-full rounded-lg border border-[#D1D2D4] bg-white py-2.5 px-3 text-sm focus:outline-none focus:border-[#FF6B6B] focus:ring-2 focus:ring-[#FF6B6B]/20"
                />
              </div>

              <div className="md:col-span-2">
                <label className="block text-sm font-medium text-[#333333] mb-1">Description</label>
                <textarea
                  name="description"
                  value={form.description}
                  onChange={handleInputChange}
                  disabled={isSaving}
                  rows={4}
                  placeholder="Describe your event... (rich text supported)"
                  className="w-full rounded-lg border border-[#D1D2D4] bg-white py-2.5 px-3 text-sm text-[#333333] placeholder:text-[#B3B3B3] focus:outline-none focus:border-[#FF6B6B] focus:ring-2 focus:ring-[#FF6B6B]/20"
                />
                <p className="mt-1 text-xs text-[#B3B3B3]">Tip: Use clear formatting. Rich editor (TipTap/TinyMCE) can replace this textarea.</p>
              </div>
            </div>
          </div>

          {/* Card: Venue */}
          <div className="bg-white rounded-xl border border-[#E3E4E6] p-6 md:p-8 shadow-sm mb-6">
            <h2 className="text-xl font-semibold text-[#333333] mb-1">Venue</h2>
            <p className="text-sm text-[#999999] mb-6">Where will the event take place?</p>
            <div className="grid md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-[#333333] mb-1">Venue Name</label>
                <input
                  name="venueName"
                  value={form.venueName}
                  onChange={handleInputChange}
                  disabled={isSaving}
                  placeholder="e.g. Central Park Arena"
                  className="w-full rounded-lg border border-[#D1D2D4] bg-white py-2.5 px-3 text-sm focus:outline-none focus:border-[#FF6B6B] focus:ring-2 focus:ring-[#FF6B6B]/20"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-[#333333] mb-1">Total Capacity</label>
                <input
                  name="capacity"
                  type="number"
                  min="1"
                  value={form.capacity}
                  onChange={handleInputChange}
                  onBlur={handleBlur}
                  disabled={isSaving}
                  placeholder="e.g. 500"
                  className={`w-full rounded-lg border bg-white py-2.5 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#FF6B6B]/20 focus:border-[#FF6B6B] ${errors.capacity ? 'border-[#FF6B6B]' : 'border-[#D1D2D4]'}`}
                />
                {errors.capacity && <p className="mt-1 text-xs text-[#FF6B6B]">{errors.capacity}</p>}
              </div>
              <div className="md:col-span-2">
                <label className="block text-sm font-medium text-[#333333] mb-1">Venue Address</label>
                <input
                  name="venueAddress"
                  value={form.venueAddress}
                  onChange={handleInputChange}
                  disabled={isSaving}
                  placeholder="e.g. 123 Main St, City — map preview appears as you type"
                  className="w-full rounded-lg border border-[#D1D2D4] bg-white py-2.5 px-3 text-sm focus:outline-none focus:border-[#FF6B6B] focus:ring-2 focus:ring-[#FF6B6B]/20"
                />
                <div className="mt-3 h-32 rounded-lg bg-[#F7F8FA] border border-dashed border-[#D1D2D4] flex items-center justify-center text-xs text-[#999999]">
                  Map placeholder — location preview
                </div>
              </div>
            </div>
          </div>

          {/* Card: Tickets */}
          <div className="bg-white rounded-xl border border-[#E3E4E6] p-6 md:p-8 shadow-sm mb-6">
            <div className="flex items-center justify-between mb-4">
              <div>
                <h2 className="text-xl font-semibold text-[#333333]">Tickets</h2>
                <p className="text-sm text-[#999999] mt-1">Add ticket tiers with different prices and quantities.</p>
              </div>
              <span className="text-xs font-medium text-[#999999] bg-[#F7F8FA] border border-[#E3E4E6] px-2 py-1 rounded-full">{tiers.length}/10 tiers</span>
            </div>

            <div className="space-y-3">
              {tiers.map((tier, i) => (
                <div key={i} className="grid grid-cols-1 md:grid-cols-12 gap-3 p-4 bg-[#F7F8FA] rounded-lg border border-[#E3E4E6]">
                  <div className="md:col-span-5">
                    <label className="block text-xs font-medium text-[#333333] mb-1">Tier Name *</label>
                    <input
                      value={tier.name}
                      onChange={(e) => updateTier(i, 'name', e.target.value)}
                      onBlur={() => {
                        if (!tier.name.trim()) setErrors((p) => ({ ...p, [`tierName-${i}`]: 'Tier name required' }));
                        else setErrors((p) => { const n = { ...p }; delete n[`tierName-${i}`]; return n; });
                      }}
                      disabled={isSaving}
                      placeholder="e.g. Regular, VIP"
                      className={`w-full rounded-lg border bg-white py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#FF6B6B]/20 focus:border-[#FF6B6B] ${errors[`tierName-${i}`] ? 'border-[#FF6B6B]' : 'border-[#D1D2D4]'}`}
                    />
                    {errors[`tierName-${i}`] && <p className="mt-1 text-xs text-[#FF6B6B]">{errors[`tierName-${i}`]}</p>}
                  </div>
                  <div className="md:col-span-3">
                    <label className="block text-xs font-medium text-[#333333] mb-1">Price</label>
                    <input
                      type="number"
                      min="0"
                      step="0.01"
                      value={tier.price}
                      onChange={(e) => updateTier(i, 'price', e.target.value)}
                      disabled={isSaving}
                      placeholder="25.00"
                      className={`w-full rounded-lg border bg-white py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#FF6B6B]/20 focus:border-[#FF6B6B] ${errors[`tierPrice-${i}`] ? 'border-[#FF6B6B]' : 'border-[#D1D2D4]'}`}
                    />
                    {errors[`tierPrice-${i}`] && <p className="mt-1 text-xs text-[#FF6B6B]">{errors[`tierPrice-${i}`]}</p>}
                  </div>
                  <div className="md:col-span-3">
                    <label className="block text-xs font-medium text-[#333333] mb-1">Quantity</label>
                    <input
                      type="number"
                      min="1"
                      value={tier.quantity}
                      onChange={(e) => updateTier(i, 'quantity', e.target.value)}
                      disabled={isSaving}
                      placeholder="100"
                      className={`w-full rounded-lg border bg-white py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-[#FF6B6B]/20 focus:border-[#FF6B6B] ${errors[`tierQuantity-${i}`] ? 'border-[#FF6B6B]' : 'border-[#D1D2D4]'}`}
                    />
                    {errors[`tierQuantity-${i}`] && <p className="mt-1 text-xs text-[#FF6B6B]">{errors[`tierQuantity-${i}`]}</p>}
                  </div>
                  <div className="md:col-span-1 flex items-end">
                    <button
                      type="button"
                      onClick={() => removeTier(i)}
                      disabled={isSaving || tiers.length <= 1}
                      className="w-full rounded-lg border border-[#E3E4E6] bg-white py-2 text-xs font-medium text-[#FF6B6B] hover:bg-[#FF6B6B] hover:text-white hover:border-[#FF6B6B] disabled:opacity-40 transition-colors"
                    >
                      Remove
                    </button>
                  </div>
                </div>
              ))}
            </div>

            <button
              type="button"
              onClick={addTier}
              disabled={isSaving}
              className="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-[#FF6B6B] border border-[#FF6B6B] hover:bg-[#FF6B6B] hover:text-white transition-colors disabled:opacity-50"
            >
              + Add Tier
            </button>
          </div>

          {/* Card: Media */}
          <div className="bg-white rounded-xl border border-[#E3E4E6] p-6 md:p-8 shadow-sm mb-6">
            <h2 className="text-xl font-semibold text-[#333333] mb-1">Media</h2>
            <p className="text-sm text-[#999999] mb-4">Event banner — recommended 1200×628 JPG/PNG</p>

            {bannerPreview ? (
              <div className="mb-4 relative group">
                <img src={bannerPreview} alt="Banner preview" className="w-full rounded-lg h-48 object-cover border border-[#E3E4E6]" />
                <button
                  type="button"
                  onClick={() => { setBannerFile(null); setBannerPreview(''); if (fileInputRef.current) fileInputRef.current.value = ''; }}
                  className="absolute top-2 right-2 bg-white/90 backdrop-blur px-3 py-1 rounded-full text-xs font-medium text-[#333333] border border-[#E3E4E6] hover:bg-white"
                >
                  Replace
                </button>
              </div>
            ) : null}

            <button
              type="button"
              onClick={() => fileInputRef.current?.click()}
              disabled={isSaving}
              className="w-full border-2 border-dashed rounded-xl p-8 text-center hover:border-[#FF6B6B] hover:bg-[#FF6B6B]/5 transition-colors group"
            >
              <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-[#F7F8FA] border border-[#E3E4E6] group-hover:border-[#FF6B6B]/30">
                <span className="text-xl">📷</span>
              </div>
              <p className="text-sm font-medium text-[#333333]">Add Cover Image</p>
              <p className="text-xs text-[#999999] mt-1">Click to upload banner image</p>
            </button>
            <input ref={fileInputRef} type="file" className="hidden" accept="image/*" onChange={handleBannerChange} />
          </div>

          {/* Sticky footer */}
          <div className="fixed bottom-0 left-0 right-0 bg-white border-t border-[#E3E4E6] shadow-[0_-4px_12px_rgba(0,0,0,0.05)]">
            <div className="mx-auto max-w-3xl px-6 py-4 flex gap-3">
              <button
                type="button"
                onClick={handleSaveDraft}
                disabled={isSaving}
                className="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 rounded-lg text-sm font-semibold bg-white text-[#333333] border border-[#D1D2D4] hover:bg-[#F7F8FA] disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                {savingDraft ? (
                  <>
                    <span className="h-4 w-4 border-2 border-[#999999] border-t-transparent rounded-full animate-spin" />
                    Saving...
                  </>
                ) : 'Save Draft'}
              </button>
              <button
                type="submit"
                disabled={isSaving}
                className="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 rounded-lg text-sm font-semibold bg-[#FF6B6B] text-white hover:bg-[#D94545] shadow-sm disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                {publishing ? (
                  <>
                    <span className="h-4 w-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                    Publishing...
                  </>
                ) : 'Publish Event'}
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  );
};

export default EventCreatePage;
