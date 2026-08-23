import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuthContext } from '../../../features/auth/context/AuthContext';
import { api } from '../../../lib/api';

const OrganizerProfileSettingsPage = () => {
  const { organizerId, user } = useAuthContext();
  const [form, setForm] = useState({
    notificationPreferences: user?.notificationPreferences ? JSON.stringify(user.notificationPreferences) : '',
    totalEventsCreated: user?.totalEventsCreated || 0,
    totalTicketsSold: user?.totalTicketsSold || 0,
  });

  const [saving, setSaving] = useState(false);

  useEffect(() => {
    if (organizerId) {
      const savedForm = localStorage.getItem(`organizer_settings_${organizerId}_form`);
      if (savedForm) {
        setForm(JSON.parse(savedForm));
      }
    }
  }, [organizerId]);

  useEffect(() => {
    if (organizerId) {
      localStorage.setItem(`organizer_settings_${organizerId}_form`, JSON.stringify(form));
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
        notificationPreferences: form.notificationPreferences ? JSON.parse(form.notificationPreferences) : [],
        totalEventsCreated: parseInt(form.totalEventsCreated) || 0,
        totalTicketsSold: parseInt(form.totalTicketsSold) || 0,
      };
      const res = await api.put('/organizer/settings', validated);
      setSaving(false);
      if (organizerId) {
        localStorage.setItem(`organizer_settings_${organizerId}_form`, JSON.stringify(form));
      }
    } catch (err) {
      setSaving(false);
      console.error('Failed to update settings:', err);
    }
  };

  return (
    <div>
      <div className="flex items-center justify-between max-w-2xl">
        <h1>Organizer Profile Settings</h1>
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
            <label className="block text-sm font-medium text-slate-900 mb-1">Notification Preferences</label>
            <p className="text-sm text-slate-500 mb-2">Controls which notifications you receive</p>
            <select
              name="notificationPreferences"
              multiple
              size={4}
              value={form.notificationPreferences ? JSON.parse(form.notificationPreferences).join(', ') : ''}
              onChange={handleInputChange}
              className="shadow-sm rounded-md border border-slate-300 w-full py-1.5 px-3 focus outline-none focus-border-indigo-500 focus:ring-indigo-500 sm:text-sm text-slate-500"
            >
              <option value="event_created">New event created</option>
              <option value="event_updated">Event updated</option>
              <option value="ticket_sold">Ticket sold</option>
              <option value="payment_received">Payment received</option>
              <option value="system_alert">System alerts</option>
              <option value="none">None</option>
            </select>
          </div>
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
            {saving ? 'Saving...' : 'Save Settings'}
          </button>
          <button
            type="button"
            onClick={() => localStorage.removeItem(`organizer_settings_${organizerId}_form`)}
            className="px-4 py-2 rounded-md bg-white text-slate-600 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-300 focus-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Reset
          </button>
        </div>
      </div>
    </div>
  );
};

export default OrganizerProfileSettingsPage;
