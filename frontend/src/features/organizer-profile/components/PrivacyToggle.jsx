import React from 'react';
import './OrganizerProfileComponents.css';

const PrivacyToggle = ({ label, description = '', checked = false, onChange, disabled = false }) => {
  const switchId = `privacy-toggle-${label.toLowerCase().replace(/[^a-z0-9]+/g, '-')}`;
  return (
    <div className="privacy-toggle">
      <div className="privacy-toggle__info">
        <span className="privacy-toggle__label" id={`${switchId}-label`}>{label}</span>
        {description && <p className="privacy-toggle__description" id={`${switchId}-description`}>{description}</p>}
      </div>
      <label className="privacy-toggle__switch" htmlFor={switchId}>
        <input
          id={switchId}
          type="checkbox"
          checked={checked}
          onChange={(e) => onChange?.(e.target.checked)}
          disabled={disabled}
          aria-describedby={description ? `${switchId}-description` : undefined}
        />
        <span className="privacy-toggle__slider" aria-hidden="true" />
      </label>
    </div>
  );
};

export default PrivacyToggle;
