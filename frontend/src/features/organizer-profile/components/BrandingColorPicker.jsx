import React, { useState } from 'react';
import './OrganizerProfileComponents.css';

const isValidHex = (value) => /^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/.test(value);

const BrandingColorPicker = ({ primaryColor = '#4ecdc4', accentColor = '#cc3838', onChange }) => {
  const [primary, setPrimary] = useState(primaryColor);
  const [accent, setAccent] = useState(accentColor);
  const [primaryError, setPrimaryError] = useState('');
  const [accentError, setAccentError] = useState('');

  const handlePrimaryChange = (e) => {
    const value = e.target.value;
    setPrimary(value);
    if (value && !isValidHex(value)) {
      setPrimaryError('Invalid hex color');
    } else {
      setPrimaryError('');
    }
    onChange?.({ primaryColor: value, accentColor: accent });
  };

  const handleAccentChange = (e) => {
    const value = e.target.value;
    setAccent(value);
    if (value && !isValidHex(value)) {
      setAccentError('Invalid hex color');
    } else {
      setAccentError('');
    }
    onChange?.({ primaryColor: primary, accentColor: value });
  };

  return (
    <div className="branding-color-picker">
      <div className="branding-color-picker__preview" style={{ background: `linear-gradient(135deg, ${primary}, ${accent})` }}>
        <span className="branding-color-picker__preview-text">Preview</span>
      </div>
      <div className="branding-color-picker__fields">
        <div className="branding-color-picker__field">
          <label htmlFor="primary-color" className="branding-color-picker__label">
            Primary Color
          </label>
          <input
            id="primary-color"
            type="color"
            value={primary}
            onChange={handlePrimaryChange}
            className="branding-color-picker__input"
          />
          <input
            type="text"
            value={primary}
            onChange={handlePrimaryChange}
            className="branding-color-picker__text"
            placeholder="#4ecdc4"
            maxLength={9}
            aria-invalid={Boolean(primaryError)}
            aria-errormessage={primaryError ? 'primary-color-error' : undefined}
          />
          {primaryError && <p id="primary-color-error" className="branding-color-picker__error" role="alert">{primaryError}</p>}
        </div>
        <div className="branding-color-picker__field">
          <label htmlFor="accent-color" className="branding-color-picker__label">
            Accent Color
          </label>
          <input
            id="accent-color"
            type="color"
            value={accent}
            onChange={handleAccentChange}
            className="branding-color-picker__input"
          />
          <input
            type="text"
            value={accent}
            onChange={handleAccentChange}
            className="branding-color-picker__text"
            placeholder="#cc3838"
            maxLength={9}
            aria-invalid={Boolean(accentError)}
            aria-errormessage={accentError ? 'accent-color-error' : undefined}
          />
          {accentError && <p id="accent-color-error" className="branding-color-picker__error" role="alert">{accentError}</p>}
        </div>
      </div>
    </div>
  );
};

export default BrandingColorPicker;
