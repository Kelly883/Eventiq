import React from 'react';
import './AuthComponents.css';

const FormField = ({
  label,
  type = 'text',
  value,
  onChange,
  onBlur,
  error = null,
  placeholder = '',
  disabled = false,
  id,
  name,
  autoComplete,
  inputMode,
  pattern,
  required = false,
  ariaInvalid = false,
  ariaDescribedBy,
  isInvalid,
  errorMessageId,
}) => {
  const inputId = id || name;
  const errorId = errorMessageId || `${inputId}-error`;

  return (
    <div className="auth-field">
      <label htmlFor={inputId} className="auth-field__label">
        {label}
        {required && <span className="auth-field__required" aria-hidden="true"> *</span>}
      </label>
      <input
        id={inputId}
        name={name}
        type={type}
        value={value}
        onChange={onChange}
        onBlur={onBlur}
        placeholder={placeholder}
        disabled={disabled}
        required={required}
        autoComplete={autoComplete}
        inputMode={inputMode}
        pattern={pattern}
        aria-invalid={ariaInvalid || Boolean(error) || isInvalid}
        aria-describedby={errorMessageId || (error ? errorId : ariaDescribedBy || undefined)}
        className={`auth-field__input ${error ? 'auth-field__input--error' : ''}`}
      />
      {error && (
        <p id={errorId} role="alert" aria-live="assertive" className="auth-field__error">
          {error}
        </p>
      )}
    </div>
  );
};

export default FormField;
