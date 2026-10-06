import React, { useId } from 'react';
import PasswordStrengthMeter from './PasswordStrengthMeter';
import './AuthComponents.css';

const PasswordField = ({
  label,
  value,
  onChange,
  onBlur,
  error = null,
  helperText = '',
  placeholder = '',
  disabled = false,
  id,
  name,
  autoComplete,
  inputMode,
  pattern,
  maxLength,
  minLength,
  required = false,
  showStrength = true,
  inputProps = {},
  ariaInvalid,
  errorMessageId,
  helperTextId,
}) => {
  const [showPassword, setShowPassword] = React.useState(false);
  const generatedId = useId();
  const inputId = id || name || generatedId;

  return (
    <div className="auth-password-field">
      <label htmlFor={inputId} className="auth-field__label">
        {label}
        {required && <span className="auth-field__required" aria-hidden="true"> *</span>}
      </label>
      <div className="auth-password-field__input-wrap">
        <input
          id={inputId}
          name={name}
          type={showPassword ? 'text' : 'password'}
          value={value}
          onChange={onChange}
          onBlur={onBlur}
          placeholder={placeholder}
          disabled={disabled}
          required={required}
          autoComplete={autoComplete}
          inputMode={inputMode}
          pattern={pattern}
          maxLength={maxLength}
          minLength={minLength}
          className={`auth-field__input ${error ? 'auth-field__input--error' : ''}`}
          aria-invalid={ariaInvalid ?? Boolean(error)}
          aria-errormessage={error ? (errorMessageId || `${inputId}-error`) : undefined}
          {...inputProps}
        />
        <button
          type="button"
          onClick={() => setShowPassword((v) => !v)}
          disabled={disabled}
          title={showPassword ? 'Hide password' : 'Show password'}
          aria-label={showPassword ? 'Hide password' : 'Show password'}
          aria-pressed={showPassword}
          className="auth-password-toggle"
        >
          {showPassword ? (
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
      </div>
      {showStrength && (
        <PasswordStrengthMeter password={value || ''} />
      )}
      {helperText && (
        <p className="auth-field__helper">
          {helperText}
        </p>
      )}
      {error && (
        <p id={`${inputId}-error`} role="alert" aria-live="assertive" className="auth-field__error">
          {error}
        </p>
      )}
    </div>
  );
};

PasswordField.defaultProps = {
  error: null,
  helperText: '',
  placeholder: '',
  disabled: false,
  required: false,
  showStrength: true,
  inputProps: {},
};

export default PasswordField;

