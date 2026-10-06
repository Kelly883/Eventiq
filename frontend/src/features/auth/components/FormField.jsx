import React, { useId } from 'react';
import './AuthComponents.css';

const AUTOCOMPLETE_MAP = {
  email: 'email',
  password: 'new-password',
  currentPassword: 'current-password',
  tel: 'tel',
  name: 'name',
  username: 'username',
};

const FormField = ({
  label,
  type = 'text',
  value,
  onChange,
  onBlur,
  error = null,
  helperText = '',
  placeholder = '',
  disabled = false,
  id,
  name,
  autoComplete: autoCompleteProp,
  inputMode,
  pattern,
  maxLength,
  minLength,
  required = false,
  ariaInvalid = false,
  ariaDescribedBy,
  isInvalid,
  errorMessageId,
  helperTextId,
}) => {
  const generatedId = useId();
  const inputId = id || name || generatedId;
  const errorId = errorMessageId || `${inputId}-error`;
  const helperId = helperTextId || `${inputId}-helper`;
  const autoComplete = autoCompleteProp
    ? AUTOCOMPLETE_MAP[autoCompleteProp] || autoCompleteProp
    : type === 'email' ? 'email' : undefined;

  const resolvedInputMode = inputMode || (type === 'email' ? 'email' : undefined);

  const describedBy = [
    error ? errorId : undefined,
    helperText ? helperId : ariaDescribedBy,
  ]
    .filter(Boolean)
    .join(' ') || undefined;

  const success = !error && !isInvalid && Boolean(value) && onBlur;

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
        inputMode={resolvedInputMode}
        pattern={pattern}
        maxLength={maxLength}
        minLength={minLength}
        aria-invalid={ariaInvalid || Boolean(error) || isInvalid}
        aria-errormessage={error ? errorId : undefined}
        aria-describedby={describedBy}
        className={`auth-field__input ${error ? 'auth-field__input--error' : ''} ${success ? 'auth-field__input--success' : ''}`}
      />
      {helperText && (
        <p id={helperId} className="auth-field__helper">
          {helperText}
        </p>
      )}
      {error && (
        <p id={errorId} role="alert" aria-live="assertive" className="auth-field__error">
          {error}
        </p>
      )}
      {!error && minLength !== undefined && (
        <p
          id={`${inputId}-minlength-error`}
          role="alert"
          aria-live="polite"
          className="auth-field__helper auth-field__minlength-error"
        >
          minimum {minLength} character{minLength !== 1 ? 's' : ''}
        </p>
      )}
      {maxLength !== undefined && (
        <p className="auth-field__helper" aria-live="polite">
          {value?.length ?? 0} / {maxLength}
        </p>
      )}
    </div>
  );
};

export default FormField;
