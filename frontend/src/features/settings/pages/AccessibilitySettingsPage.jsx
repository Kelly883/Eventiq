import React, { useState, useEffect } from 'react';
import { api } from '../../lib/api';
import { showToast } from '../../lib/api';

const DEFAULTS = {
  fontSize: 16,
  highContrast: false,
  screenReaderOptimized: false,
  focusIndicatorEnhanced: false,
  motionReduced: false,
  lineHeight: 1.5,
  letterSpacing: 0.0,
  wordSpacing: 0.0,
  colorBlindnessMode: 'none',
};

const AccessibilitySettingsPage = () => {
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [preferences, setPreferences] = useState(DEFAULTS);

  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    api
      .get('/users/me/accessibility-preferences')
      .then((response) => {
        if (cancelled) return;
        const data = response.data ?? {};
        setPreferences({
          fontSize: data.fontSize ?? DEFAULTS.fontSize,
          highContrast: data.highContrast ?? DEFAULTS.highContrast,
          screenReaderOptimized: data.screenReaderOptimized ?? DEFAULTS.screenReaderOptimized,
          focusIndicatorEnhanced: data.focusIndicatorEnhanced ?? DEFAULTS.focusIndicatorEnhanced,
          motionReduced: data.motionReduced ?? DEFAULTS.motionReduced,
          lineHeight: data.lineHeight ?? DEFAULTS.lineHeight,
          letterSpacing: data.letterSpacing ?? DEFAULTS.letterSpacing,
          wordSpacing: data.wordSpacing ?? DEFAULTS.wordSpacing,
          colorBlindnessMode: data.colorBlindnessMode ?? DEFAULTS.colorBlindnessMode,
        });
      })
      .catch(() => {
        if (!cancelled) {
          showToast('Unable to load accessibility preferences', '', 'warning');
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
      await api.patch('/users/me/accessibility-preferences/update', preferences);
      showToast('Accessibility preferences saved', '', 'success');
    } catch {
      showToast('Failed to save accessibility preferences', '', 'warning');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[40vh]">
        <p className="text-sm text-slate-500">Loading accessibility preferences...</p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-slate-900">Accessibility</h2>
          <p className="text-sm text-slate-500">Adjust how Eventiq looks and behaves for you.</p>
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
        <div className="p-4 flex items-center justify-between">
          <div>
            <div className="font-semibold text-slate-900">High contrast</div>
            <div className="text-xs text-slate-500">Increase contrast for improved readability.</div>
          </div>
          <input
            type="checkbox"
            checked={preferences.highContrast}
            onChange={(event) => updatePreference('highContrast', event.target.checked)}
          />
        </div>
        <div className="p-4 flex items-center justify-between">
          <div>
            <div className="font-semibold text-slate-900">Screen reader optimized</div>
            <div className="text-xs text-slate-500">Improve compatibility with assistive technology.</div>
          </div>
          <input
            type="checkbox"
            checked={preferences.screenReaderOptimized}
            onChange={(event) => updatePreference('screenReaderOptimized', event.target.checked)}
          />
        </div>
        <div className="p-4 flex items-center justify-between">
          <div>
            <div className="font-semibold text-slate-900">Enhanced focus indicator</div>
            <div className="text-xs text-slate-500">Make keyboard focus easier to see.</div>
          </div>
          <input
            type="checkbox"
            checked={preferences.focusIndicatorEnhanced}
            onChange={(event) => updatePreference('focusIndicatorEnhanced', event.target.checked)}
          />
        </div>
        <div className="p-4 flex items-center justify-between">
          <div>
            <div className="font-semibold text-slate-900">Reduced motion</div>
            <div className="text-xs text-slate-500">Minimize animations and transitions.</div>
          </div>
          <input
            type="checkbox"
            checked={preferences.motionReduced}
            onChange={(event) => updatePreference('motionReduced', event.target.checked)}
          />
        </div>
      </div>
    </div>
  );
};

export default AccessibilitySettingsPage;
