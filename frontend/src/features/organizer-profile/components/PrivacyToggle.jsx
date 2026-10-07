import React from 'react';
import './OrganizerProfileComponents.css';

const PrivacyToggle = ({ label, description = '', checked = false, onChange, disabled = false }) => {
  return (
    <div className="privacy-toggle">
      <div className="privacy-toggle__info">
        <span className="privacy-toggle__label">{label}</span>
        {description && <p className="privacy-toggle__description">{description}</p>}
      </div>
      <label className="privacy-toggle__switch">
        <input
          type="checkbox"
          checked={checked}
          onChange={(e) => onChange?.(e.target.checked)}
          disabled={disabled}
        />
        <span className="privacy-toggle__slider" aria-hidden="true" />
      </label>
    </div>
  );
};

export default PrivacyToggle;
