import React, { useState, useEffect } from 'react';
import { api } from '../../lib/api';
import { showToast } from '../../lib/api';

const SUPPORTED_LANGUAGES = [
  { code: 'en', label: 'English' },
  { code: 'es', label: 'Spanish' },
  { code: 'fr', label: 'French' },
  { code: 'de', label: 'German' },
  { code: 'pt', label: 'Portuguese' },
  { code: 'zh', label: 'Chinese' },
  { code: 'ja', label: 'Japanese' },
  { code: 'ar', label: 'Arabic' },
];

const LanguagePreferencePage = () => {
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [preferences, setPreferences] = useState({
    language: 'en',
    region: 'US',
    dateFormat: 'MM/DD/YYYY',
    timeFormat: '12-hour',
    currency: 'USD',
    numberFormat: 'period',
    rtlEnabled: false,
  });

  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    api
      .get('/users/me/language-preferences')
      .then((response) => {
        if (cancelled) return;
        const data = response.data ?? {};
        setPreferences({
          language: data.language ?? 'en',
          region: data.region ?? 'US',
          dateFormat: data.date_format ?? 'MM/DD/YYYY',
          timeFormat: data.time_format ?? '12-hour',
          currency: data.currency ?? 'USD',
          numberFormat: data.number_format ?? 'period',
          rtlEnabled: data.rtl_enabled ?? false,
        });
      })
      .catch(() => {
        if (!cancelled) {
          showToast('Unable to load language preferences', '', 'warning');
        }
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });
    return () => {
      cancelled = true;
    };
  }, []);

  const updatePreference = (key, value) => {
    setPreferences((prev) => ({ ...prev, [key]: value }));
  };

  const save = async () => {
    setSaving(true);
    try {
      await api.patch('/users/me/language-preferences/update', {
        language: preferences.language,
        region: preferences.region,
        dateFormat: preferences.dateFormat,
        timeFormat: preferences.timeFormat,
        currency: preferences.currency,
        numberFormat: preferences.numberFormat,
        rtlEnabled: preferences.rtlEnabled,
      });
      showToast('Language preferences saved', '', 'success');
    } catch {
      showToast('Failed to save language preferences', '', 'warning');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[40vh]">
        <p className="text-sm text-slate-500">Loading language preferences...</p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-slate-900">Language &amp; Region</h2>
          <p className="text-sm text-slate-500">Choose your preferred language and regional formats.</p>
        </div>
        <button
          type="button"
          onClick={save}
          disabled={saving}
          className="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50"
        >
          {saving ? 'Saving...' : 'Save preferences'}
        </button>
      </div>

      <div className="bg-white border border-slate-200 rounded-xl shadow-sm divide-y divide-slate-100">
        <div className="p-4">
          <div className="font-semibold text-slate-900">Language</div>
          <div className="mt-2">
            <select
              value={preferences.language}
              onChange={(event) => updatePreference('language', event.target.value)}
              className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
            >
              {SUPPORTED_LANGUAGES.map((language) => (
                <option key={language.code} value={language.code}>
                  {language.label}
                </option>
              ))}
            </select>
          </div>
        </div>
        <div className="p-4">
          <div className="font-semibold text-slate-900">Date format</div>
          <div className="mt-2">
            <select
              value={preferences.dateFormat}
              onChange={(event) => updatePreference('dateFormat', event.target.value)}
              className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
            >
              <option value="MM/DD/YYYY">MM/DD/YYYY</option>
              <option value="DD/MM/YYYY">DD/MM/YYYY</option>
              <option value="YYYY-MM-DD">YYYY-MM-DD</option>
            </select>
          </div>
        </div>
        <div className="p-4 flex items-center justify-between">
          <div>
            <div className="font-semibold text-slate-900">Right-to-left layout</div>
            <div className="text-xs text-slate-500">Enable RTL for supported languages.</div>
          </div>
          <input
            type="checkbox"
            checked={preferences.rtlEnabled}
            onChange={(event) => updatePreference('rtlEnabled', event.target.checked)}
          />
        </div>
      </div>
    </div>
  );
};

export default LanguagePreferencePage;
