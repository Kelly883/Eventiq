import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuthContext } from '../../../features/auth/context/AuthContext';
import { api } from '../../../lib/api';

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
    <div>
      <div className="flex items-center justify-between max-w-2xl">
        <h1>Edit Organizer Profile</h1>
        {organizerId && (
          <Link
            to={`/organizer/${organizerId}`}
            className="text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition-colors"
          >
            View public profile →
          </Link>
        )}
      </div>
      <div className="space-y-4">
        <form onSubmit={handleSubmit} className="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-2xl">
          <div>
            <label className="block text-sm font-medium text-slate-900 mb-1">Display Name</label>
            <input
              name="displayName"
              value={form.displayName}
              onChange={handleInputChange}
              className="shadow-sm rounded-md border border-slate-300 w-full py-2.5 px-3 focus outline-none focus-border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              required
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-slate-900 mb-1">Bio</label>
            <textarea
              name="bio"
              value={form.bio}
              onChange={handleInputChange}
              className="shadow-sm rounded-md border border-slate-300 w-full py-2.5 px-3 focus outline-none focus-border-indigo-500 focus:ring-indigo-500 sm:text-sm h-24 resize-y"
              rows={3}
              required
            ></textarea>
          </div>
          <div>
            <label className="block text-sm font-medium text-slate-900 mb-1">Avatar URL</label>
            <input
              name="avatarUrl"
              value={form.avatarUrl}
              onChange={handleInputChange}
              className="shadow-sm rounded-md border border-slate-300 w-full py-2.5 px-3 focus outline-none focus-border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-slate-900 mb-1">Email</label>
            <input
              name="email"
              value={form.email}
              onChange={handleInputChange}
              type="email"
              className="shadow-sm rounded-md border border-slate-300 w-full py-2.5 px-3 focus outline-none focus-border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              required
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-slate-900 mb-1">Phone</label>
            <input
              name="phone"
              value={form.phone}
              onChange={handleInputChange}
              className="shadow-sm rounded-md border border-slate-300 w-full py-2.5 px-3 focus outline-none focus-border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-slate-900 mb-1">Website</label>
            <input
              name="website"
              value={form.website}
              onChange={handleInputChange}
              className="shadow-sm rounded-md border border-slate-300 w-full py-2.5 px-3 focus outline-none focus-border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
          </div>
          <hr className="my-4" />
          <div>
            <label className="block text-sm font-medium text-slate-900 mb-1">Timezone</label>
            <input
              name="timezone"
              value={form.timezone}
              onChange={handleInputChange}
              className="shadow-sm rounded-md border border-slate-300 w-full py-2.5 px-3 focus outline-none focus-border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-slate-900 mb-1">Currency</label>
            <input
              name="currency"
              value={form.currency}
              onChange={handleInputChange}
              className="shadow-sm rounded-md border border-slate-300 w-full py-2.5 px-3 focus outline-none focus-border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-slate-900 mb-1">Country</label>
            <input
              name="country"
              value={form.country}
              onChange={handleInputChange}
              className="shadow-sm rounded-md border border-slate-300 w-full py-2.5 px-3 focus outline-none focus-border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
          </div>
          <hr className="my-4" />
          <div>
            <label className="block text-sm font-medium text-slate-900 mb-1">Verification Status</label>
            <select
              name="verificationStatus"
              value={form.verificationStatus}
              onChange={handleInputChange}
              className="shadow-sm rounded-md border border-slate-300 w-full py-2.5 px-3 focus outline-none focus-border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
              <option value="unverified">Unverified</option>
              <option value="verified">Verified</option>
              <option value="pending">Pending</option>
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium text-slate-900 mb-1">Payment Default</label>
            <select
              name="paymentDefault"
              value={form.paymentDefault}
              onChange={handleInputChange}
              className="shadow-sm rounded-md border border-slate-300 w-full py-2.5 px-3 focus outline-none focus-border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
              <option value="true">Stripe</option>
              <option value="false">PayPal</option>
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium text-slate-900 mb-1">Commission Rate</label>
            <input
              name="commissionRate"
              value={form.commissionRate}
              onChange={handleInputChange}
              type="number"
              className="shadow-sm rounded-md border border-slate-300 w-full py-2.5 px-3 focus outline-none focus-border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              min="0"
              step="0.01"
            />
          </div>
          <hr className="my-4" />
          <div className="flex items-center gap-2">
            <label className="block text-sm font-medium text-slate-900 mb-1">
              <input
                type="checkbox"
                name="isPublic"
                checked={form.isPublic}
                onChange={handleInputChange}
                className="rounded border border-slate-300 w-4 h-4 focus-border-indigo-500 focus:ring-indigo-500"
              />
              Profile Public
            </label>
            <label className="block text-sm font-medium text-slate-900 mb-1">
              <input
                type="checkbox"
                name="emailPublic"
                checked={form.emailPublic}
                onChange={handleInputChange}
                className="rounded border border-slate-300 w-4 h-4 focus-border-indigo-500 focus:ring-indigo-500"
              />
              Email Public
            </label>
          </div>
          <div>
            <label className="block text-sm font-medium text-slate-900 mb-1">
              <input
                type="checkbox"
                name="phonePublic"
                checked={form.phonePublic}
                onChange={handleInputChange}
                className="rounded border border-slate-300 w-4 h-4 focus-border-indigo-500 focus:ring-indigo-500"
              />
              Phone Public
            </label>
          </div>
          <hr className="my-4" />
          <div>
            <label className="block text-sm font-medium text-slate-900 mb-1">Total Events Created</label>
            <input
              name="totalEventsCreated"
              value={form.totalEventsCreated}
              onChange={handleInputChange}
              type="number"
              className="shadow-sm rounded-md border border-slate-300 w-full py-2.5 px-3 focus outline-none focus-border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              min="0"
              required
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-slate-900 mb-1">Total Tickets Sold</label>
            <input
              name="totalTicketsSold"
              value={form.totalTicketsSold}
              onChange={handleInputChange}
              type="number"
              className="shadow-sm rounded-md border border-slate-300 w-full py-2.5 px-3 focus outline-none focus-border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              min="0"
              required
            />
          </div>
        </form>
        <div className="flex justify-end space-x-2 pt-4">
          <button
            type="submit"
            disabled={saving}
            className="px-4 py-2 rounded-md bg-indigo-600 text-white font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {saving ? 'Saving...' : 'Save Profile'}
          </button>
          <button
            type="button"
            onClick={() => localStorage.removeItem(`organizer_profile_${organizerId}_form`)}
            className="px-4 py-2 rounded-md bg-white text-slate-600 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-300 focus-offset-2"
          >
            Reset
          </button>
        </div>
      </div>
    </div>
  );
};

export default OrganizerProfileEditPage;
