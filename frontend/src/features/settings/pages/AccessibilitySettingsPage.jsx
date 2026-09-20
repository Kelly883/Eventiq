import React, { useState, useEffect } from 'react';
import { api, showToast } from '../../../lib/api';
import Skeleton from '../../../components/Skeleton';

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

const FONT_SIZE_OPTIONS = [12, 14, 16, 18, 20, 22, 24];
const LINE_HEIGHT_OPTIONS = [1.0, 1.15, 1.5, 1.75, 2.0];
const COLOR_BLINDNESS_OPTIONS = [
  { value: 'none', label: 'None' },
  { value: 'protanopia', label: 'Protanopia (red-weak)' },
  { value: 'deuteranopia', label: 'Deuteranopia (green-weak)' },
  { value: 'tritanopia', label: 'Tritanopia (blue-weak)' },
];

const Toggle = ({ checked, onChange, disabled, id, label }) => (
  <button
    type="button"
    role="switch"
    id={id}
    aria-checked={checked}
    aria-label={label}
    disabled={disabled}
    onClick={() => onChange(!checked)}
    className={`relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus:outline-none focus:ring-2 focus:ring-[#FF6B6B] focus:ring-offset-2 ${
      checked ? 'bg-[#FF6B6B]' : 'bg-[#E3E4E6]'
    } ${disabled ? 'opacity-50 cursor-not-allowed' : ''}`}
  >
    <span
      className={`inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform ${
        checked ? 'translate-x-5' : 'translate-x-0'
      }`}
    />
  </button>
);

const RangeField = ({ label, description, value, onChange, min, max, step, unit, id }) => (
  <div className="py-4">
    <div className="flex items-center justify-between">
      <div>
        <label htmlFor={id} className="text-sm font-semibold text-[#333333]">
          {label}
        </label>
        {description && (
          <p className="text-xs text-[#999999] mt-0.5">{description}</p>
        )}
      </div>
      <span className="text-sm font-medium text-[#666666] tabular-nums min-w-[3rem] text-right">
        {value}{unit}
      </span>
    </div>
    <div className="mt-3">
      <input
        type="range"
        id={id}
        min={min}
        max={max}
        step={step}
        value={value}
        onChange={(e) => onChange(parseFloat(e.target.value))}
        aria-valuemin={min}
        aria-valuemax={max}
        aria-valuenow={value}
        className="w-full h-2 bg-[#E3E4E6] rounded-full appearance-none cursor-pointer accent-[#FF6B6B]"
      />
    </div>
  </div>
);

const SelectField = ({ label, description, value, onChange, options, id }) => (
  <div className="py-4">
    <label htmlFor={id} className="text-sm font-semibold text-[#333333]">
      {label}
    </label>
    {description && (
      <p className="text-xs text-[#999999] mt-0.5 mb-2">{description}</p>
    )}
    <select
      id={id}
      value={value}
      onChange={(e) => onChange(e.target.value)}
      className="w-full rounded-lg border border-[#E3E4E6] bg-white px-3 py-2 text-sm text-[#333333] focus:outline-none focus:ring-2 focus:ring-[#FF6B6B] focus:border-[#FF6B6B]"
    >
      {options.map((option) => (
        <option key={option.value} value={option.value}>
          {option.label}
        </option>
      ))}
    </select>
  </div>
);

const AccessibilitySettingsPage = () => {
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState(null);
  const [preferences, setPreferences] = useState(DEFAULTS);

  useEffect(() => {
    let cancelled = false;
    setLoading(true);
    setError(null);
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
          setError('Unable to load accessibility preferences. Please refresh and try again.');
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

  const resetToDefaults = () => {
    setPreferences(DEFAULTS);
    showToast('Preferences reset to defaults', '', 'info');
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
      <div className="space-y-6">
        <Skeleton variant="text" className="h-8 w-48" />
        <Skeleton variant="text" className="h-4 w-96" />
        <Skeleton variant="card" className="h-64" />
        <Skeleton variant="card" className="h-48" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h2 className="text-2xl font-bold text-[#333333]">Accessibility</h2>
          <p className="text-sm text-[#666666] mt-1">
            Customize how Eventiq looks and behaves to match your accessibility needs.
          </p>
        </div>
        <div className="flex items-center gap-3">
          <button
            type="button"
            onClick={resetToDefaults}
            disabled={saving}
            className="px-4 py-2 rounded-lg border border-[#E3E4E6] bg-white text-[#333333] text-sm font-medium hover:bg-[#F7F8FA] disabled:opacity-50 transition-colors"
          >
            Reset to defaults
          </button>
          <button
            type="button"
            onClick={save}
            disabled={saving}
            className="px-4 py-2 rounded-lg bg-[#FF6B6B] text-white text-sm font-semibold hover:bg-[#D94545] disabled:opacity-50 transition-colors"
          >
            {saving ? 'Saving...' : 'Save preferences'}
          </button>
        </div>
      </div>

      {error && (
        <div className="p-4 bg-[#fef2f2] border border-[#fecaca] rounded-lg text-sm text-[#dc2626]">
          {error}
        </div>
      )}

      {/* Display Preferences */}
      <div className="bg-white border border-[#E3E4E6] rounded-xl shadow-sm">
        <div className="p-4 sm:p-6">
          <h3 className="text-base font-semibold text-[#333333]">Display Preferences</h3>
          <p className="text-xs text-[#999999] mt-1">
            Adjust text size, spacing, and color settings for better readability.
          </p>
        </div>
        <div className="divide-y divide-[#E3E4E6]">
          <div className="px-4 sm:px-6">
            <SelectField
              id="font-size"
              label="Font size"
              description="Adjust the base text size across the app."
              value={String(preferences.fontSize)}
              onChange={(value) => updatePreference('fontSize', parseInt(value, 10))}
              options={FONT_SIZE_OPTIONS.map((size) => ({ value: String(size), label: `${size}px` }))}
            />
          </div>
          <div className="px-4 sm:px-6">
            <SelectField
              id="line-height"
              label="Line height"
              description="Increase or decrease the space between lines of text."
              value={String(preferences.lineHeight)}
              onChange={(value) => updatePreference('lineHeight', parseFloat(value))}
              options={LINE_HEIGHT_OPTIONS.map((h) => ({ value: String(h), label: `${h}x` }))}
            />
          </div>
          <div className="px-4 sm:px-6">
            <RangeField
              id="letter-spacing"
              label="Letter spacing"
              description="Adjust the space between individual characters."
              value={preferences.letterSpacing}
              onChange={(value) => updatePreference('letterSpacing', value)}
              min={0}
              max={0.2}
              step={0.02}
              unit="em"
            />
          </div>
          <div className="px-4 sm:px-6">
            <RangeField
              id="word-spacing"
              label="Word spacing"
              description="Adjust the space between words."
              value={preferences.wordSpacing}
              onChange={(value) => updatePreference('wordSpacing', value)}
              min={0}
              max={0.2}
              step={0.02}
              unit="em"
            />
          </div>
          <div className="px-4 sm:px-6">
            <SelectField
              id="color-blindness-mode"
              label="Color blindness mode"
              description="Apply a color filter to help with color perception."
              value={preferences.colorBlindnessMode}
              onChange={(value) => updatePreference('colorBlindnessMode', value)}
              options={COLOR_BLINDNESS_OPTIONS}
            />
          </div>
        </div>
      </div>

      {/* Interaction Preferences */}
      <div className="bg-white border border-[#E3E4E6] rounded-xl shadow-sm">
        <div className="p-4 sm:p-6">
          <h3 className="text-base font-semibold text-[#333333]">Interaction Preferences</h3>
          <p className="text-xs text-[#999999] mt-1">
            Control how you interact with Eventiq using keyboard, screen readers, and motion.
          </p>
        </div>
        <div className="divide-y divide-[#E3E4E6]">
          <div className="flex items-center justify-between gap-4 px-4 sm:px-6 py-4">
            <div>
              <label htmlFor="high-contrast" className="text-sm font-semibold text-[#333333]">
                High contrast
              </label>
              <p className="text-xs text-[#999999] mt-0.5">Increase contrast for improved readability.</p>
            </div>
            <Toggle
              id="high-contrast"
              checked={preferences.highContrast}
              onChange={(value) => updatePreference('highContrast', value)}
              disabled={saving}
              label="High contrast"
            />
          </div>
          <div className="flex items-center justify-between gap-4 px-4 sm:px-6 py-4">
            <div>
              <label htmlFor="enhanced-focus" className="text-sm font-semibold text-[#333333]">
                Enhanced focus indicator
              </label>
              <p className="text-xs text-[#999999] mt-0.5">Make keyboard focus easier to see.</p>
            </div>
            <Toggle
              id="enhanced-focus"
              checked={preferences.focusIndicatorEnhanced}
              onChange={(value) => updatePreference('focusIndicatorEnhanced', value)}
              disabled={saving}
              label="Enhanced focus indicator"
            />
          </div>
          <div className="flex items-center justify-between gap-4 px-4 sm:px-6 py-4">
            <div>
              <label htmlFor="reduced-motion" className="text-sm font-semibold text-[#333333]">
                Reduced motion
              </label>
              <p className="text-xs text-[#999999] mt-0.5">Minimize animations and transitions.</p>
            </div>
            <Toggle
              id="reduced-motion"
              checked={preferences.motionReduced}
              onChange={(value) => updatePreference('motionReduced', value)}
              disabled={saving}
              label="Reduced motion"
            />
          </div>
          <div className="flex items-center justify-between gap-4 px-4 sm:px-6 py-4">
            <div>
              <label htmlFor="screen-reader" className="text-sm font-semibold text-[#333333]">
                Screen reader optimized
              </label>
              <p className="text-xs text-[#999999] mt-0.5">Improve compatibility with assistive technology.</p>
            </div>
            <Toggle
              id="screen-reader"
              checked={preferences.screenReaderOptimized}
              onChange={(value) => updatePreference('screenReaderOptimized', value)}
              disabled={saving}
              label="Screen reader optimized"
            />
          </div>
        </div>
      </div>
    </div>
  );
};

export default AccessibilitySettingsPage;
