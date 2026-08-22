import React, { useEffect, useState, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { organizerService } from '../services';

const OrganizerProfileEditPage = () => {
  const navigate = useNavigate();
  const [profile, setProfile] = useState(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);
  const [profileExists, setProfileExists] = useState(true);

  const [form, setForm] = useState({
    displayName: '',
    bio: '',
    avatarUrl: '',
    email: '',
    phone: '',
    website: '',
    timezone: '',
    currency: '',
    country: '',
    isPublic: true,
    emailPublic: false,
    phonePublic: false,
    hideSocialLinks: false,
    hideBrandingColors: false,
    socialLinks: {},
    brandingColors: {},
  });

  const fetchProfile = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);
      const data = await organizerService.getProfile();
      setProfileExists(data.profile_exists !== false);
      if (data.data) {
        setForm({
          displayName: data.data.displayName || '',
          bio: data.data.bio || '',
          avatarUrl: data.data.avatarUrl || '',
          email: data.data.email || '',
          phone: data.data.phone || '',
          website: data.data.website || '',
          timezone: data.data.timezone || '',
          currency: data.data.currency || '',
          country: data.data.country || '',
          isPublic: data.data.isPublic ?? true,
          emailPublic: data.data.emailPublic ?? false,
          phonePublic: data.data.phonePublic ?? false,
          hideSocialLinks: data.data.hideSocialLinks ?? false,
          hideBrandingColors: data.data.hideBrandingColors ?? false,
          socialLinks: data.data.socialLinks || {},
          brandingColors: data.data.brandingColors || {},
        });
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to load profile');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchProfile();
  }, [fetchProfile]);

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setForm((prev) => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value,
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      setSaving(true);
      setError(null);
      setSuccess(null);
      await organizerService.updateProfile(form);
      setSuccess('Profile saved successfully');
      setProfileExists(true);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to save profile');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-slate-50 flex items-center justify-center">
        <div className="text-slate-500 font-semibold">Loading profile...</div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-50">
      <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-10">
        <button
          onClick={() => navigate(-1)}
          className="mb-6 inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-semibold text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 transition-colors"
        >
          ← Back
        </button>

        <div className="bg-white rounded-xl border border-slate-100 shadow-sm p-8">
          <h1 className="text-2xl font-extrabold text-slate-900 mb-2">
            {profileExists ? 'Edit Profile' : 'Create Profile'}
          </h1>
          <p className="text-sm text-slate-500 mb-6">
            {profileExists
              ? 'Update your public organizer profile.'
              : 'Set up your public organizer profile to get started.'}
          </p>

          {error && (
            <div className="mb-4 p-3 rounded-lg bg-rose-50 text-rose-700 text-sm font-medium border border-rose-100">
              {error}
            </div>
          )}
          {success && (
            <div className="mb-4 p-3 rounded-lg bg-emerald-50 text-emerald-700 text-sm font-medium border border-emerald-100">
              {success}
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-6">
            <div>
              <label className="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                Display Name *
              </label>
              <input
                type="text"
                name="displayName"
                value={form.displayName}
                onChange={handleChange}
                required
                className="w-full bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium p-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                Bio
              </label>
              <textarea
                name="bio"
                value={form.bio}
                onChange={handleChange}
                rows={4}
                className="w-full bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium p-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                  Email
                </label>
                <input
                  type="email"
                  name="email"
                  value={form.email}
                  onChange={handleChange}
                  className="w-full bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium p-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
              </div>
              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                  Phone
                </label>
                <input
                  type="tel"
                  name="phone"
                  value={form.phone}
                  onChange={handleChange}
                  className="w-full bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium p-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
              </div>
            </div>

            <div>
              <label className="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                Website
              </label>
              <input
                type="url"
                name="website"
                value={form.website}
                onChange={handleChange}
                className="w-full bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium p-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
            </div>

            <div>
              <label className="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                Avatar URL
              </label>
              <input
                type="url"
                name="avatarUrl"
                value={form.avatarUrl}
                onChange={handleChange}
                className="w-full bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium p-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                  Timezone
                </label>
                <input
                  type="text"
                  name="timezone"
                  value={form.timezone}
                  onChange={handleChange}
                  className="w-full bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium p-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
              </div>
              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                  Currency
                </label>
                <input
                  type="text"
                  name="currency"
                  value={form.currency}
                  onChange={handleChange}
                  className="w-full bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium p-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
              </div>
              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                  Country
                </label>
                <input
                  type="text"
                  name="country"
                  value={form.country}
                  onChange={handleChange}
                  className="w-full bg-slate-50 border border-slate-200 text-slate-700 text-sm font-medium p-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
              </div>
            </div>

            <div className="flex items-center gap-2">
              <input
                type="checkbox"
                name="isPublic"
                id="isPublic"
                checked={form.isPublic}
                onChange={handleChange}
                className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
              />
              <label htmlFor="isPublic" className="text-sm text-slate-700 font-medium">
                Public profile
              </label>
            </div>

            <div className="flex items-center gap-2">
              <input
                type="checkbox"
                name="emailPublic"
                id="emailPublic"
                checked={form.emailPublic}
                onChange={handleChange}
                className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
              />
              <label htmlFor="emailPublic" className="text-sm text-slate-700 font-medium">
                Show email publicly
              </label>
            </div>

            <div className="flex items-center gap-2">
              <input
                type="checkbox"
                name="phonePublic"
                id="phonePublic"
                checked={form.phonePublic}
                onChange={handleChange}
                className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
              />
              <label htmlFor="phonePublic" className="text-sm text-slate-700 font-medium">
                Show phone publicly
              </label>
            </div>

            <div className="pt-4 flex items-center gap-3">
              <button
                type="submit"
                disabled={saving}
                className="px-6 py-2.5 rounded-lg bg-indigo-600 text-white text-sm font-bold shadow-sm hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {saving ? 'Saving...' : profileExists ? 'Save Changes' : 'Create Profile'}
              </button>
              <button
                type="button"
                onClick={() => navigate(-1)}
                className="px-6 py-2.5 rounded-lg bg-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-300 transition-colors"
              >
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
};

export default OrganizerProfileEditPage;
