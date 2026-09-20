import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuthContext } from '../../../features/auth/context/AuthContext';
import { api } from '../../../lib/api';
import '../../../styles/shared-pages.css';

const OrganizerProfileEditPage = () => {
  const { organizerId, user } = useAuthContext();
  const [form, setForm] = useState({
    displayName: user?.displayName || '',
    bio: user?.bio || '',
    avatarUrl: user?.avatarUrl || '',
    email: user?.email || '',
    phone: user?.phone || '',
    website: user?.website || '',
    socialLinks: user?.socialLinks ? JSON.stringify(user.socialLinks) : '',
    brandingColors: user?.brandingColors ? JSON.stringify(user.brandingColors) : '',
    timezone: user?.timezone || '',
    currency: user?.currency || '',
    country: user?.country || '',
    verificationStatus: user?.verificationStatus || 'unverified',
    paymentDefault: user?.paymentDefault || 'false',
    commissionRate: user?.commissionRate ? user.commissionRate.toString() : '0',
    isPublic: user?.isPublic || false,
    emailPublic: user?.emailPublic || false,
    phonePublic: user?.phonePublic || false,
    hideSocialLinks: user?.hideSocialLinks || false,
    hideBrandingColors: user?.hideBrandingColors || false,
    notificationPreferences: user?.notificationPreferences ? JSON.stringify(user.notificationPreferences) : '',
    totalEventsCreated: user?.totalEventsCreated || 0,
    totalTicketsSold: user?.totalTicketsSold || 0,
  });

  const [saving, setSaving] = useState(false);

  useEffect(() => {
    if (organizerId && user) {
      const savedForm = JSON.parse(localStorage.getItem(`organizer_profile_${organizerId}_form`));
      if (savedForm) {
        setForm(savedForm);
      }
    }
  }, [organizerId, user]);

  useEffect(() => {
    if (organizerId) {
      localStorage.setItem(`organizer_profile_${organizerId}_form`, JSON.stringify(form));
    }
  }, [organizerId, form]);

  const handleInputChange = (e) => {
    const { name, value, type, checked } = e.target;
    setForm(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value,
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    try {
      const validated = {
        displayName: form.displayName,
        bio: form.bio,
        avatarUrl: form.avatarUrl,
        email: form.email,
        phone: form.phone,
        website: form.website,
        socialLinks: form.socialLinks ? JSON.parse(form.socialLinks) : [],
        brandingColors: form.brandingColors ? JSON.parse(form.brandingColors) : [],
        timezone: form.timezone,
        currency: form.currency,
        country: form.country,
        verificationStatus: form.verificationStatus,
        paymentDefault: form.paymentDefault,
        commissionRate: parseFloat(form.commissionRate) || 0,
        isPublic: form.isPublic,
        emailPublic: form.emailPublic,
        phonePublic: form.phonePublic,
        hideSocialLinks: form.hideSocialLinks,
        hideBrandingColors: form.hideBrandingColors,
        notificationPreferences: form.notificationPreferences ? JSON.parse(form.notificationPreferences) : [],
        totalEventsCreated: parseInt(form.totalEventsCreated) || 0,
        totalTicketsSold: parseInt(form.totalTicketsSold) || 0,
      };
      const res = await api.put('/organizer/profile', validated);
      setSaving(false);
      // Save to localStorage for offline
      if (organizerId) {
        localStorage.setItem(`organizer_profile_${organizerId}_form`, JSON.stringify(form));
      }
    } catch (err) {
      setSaving(false);
      console.error('Failed to update profile:', err);
    }
  };

  return (
    <div className="spa-page">
      <div className="spa-container" style={{ maxWidth: '768px' }}>
        <div
          style={{
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            marginBottom: '24px',
            flexWrap: 'wrap',
            gap: '12px',
          }}
        >
          <h1 className="spa-page__title" style={{ fontSize: '1.875rem' }}>
            Edit Organizer Profile
          </h1>
          {organizerId && (
            <Link
              to={`/o/${organizerId}`}
              style={{
                fontSize: '14px',
                fontWeight: 600,
                color: '#4f46e5',
              }}
            >
              View public profile →
            </Link>
          )}
        </div>

        <div className="spa-card spa-card--padded">
          <form onSubmit={handleSubmit}>
            <div
              style={{
                display: 'grid',
                gridTemplateColumns: 'repeat(2, 1fr)',
                gap: '16px',
                maxWidth: '768px',
              }}
            >
              <div className="spa-field">
                <label htmlFor="displayName">Display Name</label>
                <input
                  id="displayName"
                  name="displayName"
                  value={form.displayName}
                  onChange={handleInputChange}
                  className="spa-input"
                  required
                />
              </div>
              <div className="spa-field">
                <label htmlFor="bio">Bio</label>
                <textarea
                  id="bio"
                  name="bio"
                  value={form.bio}
                  onChange={handleInputChange}
                  className="spa-input"
                  style={{ height: '96px', resize: 'vertical' }}
                  rows={3}
                  required
                />
              </div>
              <div className="spa-field">
                <label htmlFor="avatarUrl">Avatar URL</label>
                <input
                  id="avatarUrl"
                  name="avatarUrl"
                  value={form.avatarUrl}
                  onChange={handleInputChange}
                  className="spa-input"
                />
              </div>
              <div className="spa-field">
                <label htmlFor="email">Email</label>
                <input
                  id="email"
                  name="email"
                  value={form.email}
                  onChange={handleInputChange}
                  type="email"
                  className="spa-input"
                  required
                />
              </div>
              <div className="spa-field">
                <label htmlFor="phone">Phone</label>
                <input
                  id="phone"
                  name="phone"
                  value={form.phone}
                  onChange={handleInputChange}
                  className="spa-input"
                />
              </div>
              <div className="spa-field">
                <label htmlFor="website">Website</label>
                <input
                  id="website"
                  name="website"
                  value={form.website}
                  onChange={handleInputChange}
                  className="spa-input"
                />
              </div>
              <div style={{ gridColumn: '1 / -1' }}>
                <hr style={{ border: 'none', borderTop: '1px solid #E3E4E6', margin: '16px 0' }} />
              </div>
              <div className="spa-field">
                <label htmlFor="timezone">Timezone</label>
                <input
                  id="timezone"
                  name="timezone"
                  value={form.timezone}
                  onChange={handleInputChange}
                  className="spa-input"
                />
              </div>
              <div className="spa-field">
                <label htmlFor="currency">Currency</label>
                <input
                  id="currency"
                  name="currency"
                  value={form.currency}
                  onChange={handleInputChange}
                  className="spa-input"
                />
              </div>
              <div className="spa-field">
                <label htmlFor="country">Country</label>
                <input
                  id="country"
                  name="country"
                  value={form.country}
                  onChange={handleInputChange}
                  className="spa-input"
                />
              </div>
              <div style={{ gridColumn: '1 / -1' }}>
                <hr style={{ border: 'none', borderTop: '1px solid #E3E4E6', margin: '16px 0' }} />
              </div>
              <div className="spa-field">
                <label htmlFor="verificationStatus">Verification Status</label>
                <select
                  id="verificationStatus"
                  name="verificationStatus"
                  value={form.verificationStatus}
                  onChange={handleInputChange}
                  className="spa-input"
                >
                  <option value="unverified">Unverified</option>
                  <option value="verified">Verified</option>
                  <option value="pending">Pending</option>
                </select>
              </div>
              <div className="spa-field">
                <label htmlFor="paymentDefault">Payment Default</label>
                <select
                  id="paymentDefault"
                  name="paymentDefault"
                  value={form.paymentDefault}
                  onChange={handleInputChange}
                  className="spa-input"
                >
                  <option value="true">Paystack</option>
                  <option value="false">Flutterwave</option>
                </select>
              </div>
              <div className="spa-field">
                <label htmlFor="commissionRate">Commission Rate</label>
                <input
                  id="commissionRate"
                  name="commissionRate"
                  value={form.commissionRate}
                  onChange={handleInputChange}
                  type="number"
                  className="spa-input"
                  min="0"
                  step="0.01"
                />
              </div>
              <div style={{ gridColumn: '1 / -1' }}>
                <hr style={{ border: 'none', borderTop: '1px solid #E3E4E6', margin: '16px 0' }} />
              </div>
              <div className="spa-field" style={{ flexDirection: 'row', alignItems: 'center', gap: '8px' }}>
                <input
                  id="isPublic"
                  type="checkbox"
                  name="isPublic"
                  checked={form.isPublic}
                  onChange={handleInputChange}
                  style={{ width: '16px', height: '16px' }}
                />
                <label htmlFor="isPublic" style={{ marginBottom: 0 }}>Profile Public</label>
              </div>
              <div className="spa-field" style={{ flexDirection: 'row', alignItems: 'center', gap: '8px' }}>
                <input
                  id="emailPublic"
                  type="checkbox"
                  name="emailPublic"
                  checked={form.emailPublic}
                  onChange={handleInputChange}
                  style={{ width: '16px', height: '16px' }}
                />
                <label htmlFor="emailPublic" style={{ marginBottom: 0 }}>Email Public</label>
              </div>
              <div className="spa-field" style={{ flexDirection: 'row', alignItems: 'center', gap: '8px' }}>
                <input
                  id="phonePublic"
                  type="checkbox"
                  name="phonePublic"
                  checked={form.phonePublic}
                  onChange={handleInputChange}
                  style={{ width: '16px', height: '16px' }}
                />
                <label htmlFor="phonePublic" style={{ marginBottom: 0 }}>Phone Public</label>
              </div>
              <div style={{ gridColumn: '1 / -1' }}>
                <hr style={{ border: 'none', borderTop: '1px solid #E3E4E6', margin: '16px 0' }} />
              </div>
              <div className="spa-field">
                <label htmlFor="totalEventsCreated">Total Events Created</label>
                <input
                  id="totalEventsCreated"
                  name="totalEventsCreated"
                  value={form.totalEventsCreated}
                  onChange={handleInputChange}
                  type="number"
                  className="spa-input"
                  min="0"
                  required
                />
              </div>
              <div className="spa-field">
                <label htmlFor="totalTicketsSold">Total Tickets Sold</label>
                <input
                  id="totalTicketsSold"
                  name="totalTicketsSold"
                  value={form.totalTicketsSold}
                  onChange={handleInputChange}
                  type="number"
                  className="spa-input"
                  min="0"
                  required
                />
              </div>
            </div>
          </form>
        </div>

        <div
          style={{
            display: 'flex',
            justifyContent: 'flex-end',
            gap: '8px',
            marginTop: '16px',
          }}
        >
          <button
            type="submit"
            disabled={saving}
            className="spa-btn spa-btn--primary"
          >
            {saving ? 'Saving...' : 'Save Profile'}
          </button>
          <button
            type="button"
            onClick={() => localStorage.removeItem(`organizer_profile_${organizerId}_form`)}
            className="spa-btn spa-btn--secondary"
          >
            Reset
          </button>
        </div>
      </div>
    </div>
  );
};

export default OrganizerProfileEditPage;
