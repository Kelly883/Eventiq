import React from 'react';
import './AuthComponents.css';

/**
 * SubmitButton renders a form submit button with optional loading spinner,
 * disabled state, and visual variants.
 */
const SubmitButton = ({ loading = false, disabled = false, children, type = 'submit', className = '', variant = 'primary', dataTestId, label, ...props }) => {
  const accessibleLabel = label || (typeof children === 'string' ? children : undefined);
  const variantClass = variant === 'success' ? 'auth-submit--success' : variant === 'danger' ? 'auth-submit--danger' : '';
  const baseDisabled = disabled || loading;
  return (
    <button
      type={type}
      disabled={baseDisabled}
      className={`auth-submit ${className} ${variantClass}`.trim()}
      {...props}
      data-testid={dataTestId || 'submit-button'}
      aria-busy={loading || undefined}
      aria-label={accessibleLabel}
    >
      {loading && (
        <span className="auth-submit__spinner" aria-hidden="true" />
      )}
      <span className={loading ? 'auth-submit__text--loading' : ''}>
        {children}
      </span>
      <span
        aria-live="polite"
        style={{
          position: 'absolute',
          width: '1px',
          height: '1px',
          padding: 0,
          margin: '-1px',
          overflow: 'hidden',
          clip: 'rect(0, 0, 0, 0)',
          whiteSpace: 'nowrap',
          borderWidth: 0,
        }}
      >
        {loading ? 'Submitting…' : 'Submit'}
      </span>
    </button>
  );
};

SubmitButton.defaultProps = {
  loading: false,
  disabled: false,
  type: 'submit',
  className: '',
  variant: 'primary',
  dataTestId: undefined,
  label: undefined,
};

export default SubmitButton;
