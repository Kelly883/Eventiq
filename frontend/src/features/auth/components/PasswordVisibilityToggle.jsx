import React, { useId } from 'react';
import './AuthComponents.css';

/**
 * PasswordVisibilityToggle is an icon button that toggles password input
 * between plain text and masked dots.
 */
const PasswordVisibilityToggle = ({ onClick, visible, disabled = false, dataTestId }) => {
  const generatedId = useId();
  const handleKeyDown = (event) => {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      onClick();
    }
  };

  const statusId = `${generatedId}-password-visibility-status`;

  return (
    <div className="auth-password-field">
      <button
        type="button"
        onClick={onClick}
        onKeyDown={handleKeyDown}
        disabled={disabled}
        title={visible ? 'Hide password' : 'Show password'}
        aria-label={visible ? 'Hide password' : 'Show password'}
        aria-pressed={visible}
        aria-describedby={statusId}
        className="auth-password-toggle"
        data-testid={dataTestId || 'password-visibility-toggle'}
      >
        {visible ? (
          <svg
            width="20"
            height="20"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
          >
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" />
            <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" />
            <line x1="1" y1="1" x2="23" y2="23" />
            <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24" />
          </svg>
        ) : (
          <svg
            width="20"
            height="20"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
          >
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
            <circle cx="12" cy="12" r="3" />
          </svg>
        )}
      </button>
      <span
        id={statusId}
        className="auth-password-visibility-status"
        aria-live="polite"
      >
        {visible ? 'Password visible' : 'Password hidden'}
      </span>
    </div>
  );
};

PasswordVisibilityToggle.defaultProps = {
  disabled: false,
  dataTestId: undefined,
};

export default PasswordVisibilityToggle;
