import React from 'react';
import './AuthComponents.css';

const SubmitButton = ({ loading = false, disabled = false, children, type = 'submit', className = '', variant = 'primary', ...props }) => {
  const variantClass = variant === 'success' ? 'auth-submit--success' : variant === 'danger' ? 'auth-submit--danger' : '';
  const baseDisabled = disabled || loading;
  return (
    <button
      type={type}
      disabled={baseDisabled}
      className={`auth-submit ${className} ${variantClass}`.trim()}
      {...props}
      data-testid="submit-button"
      aria-busy={loading || undefined}
    >
      {loading && (
        <span className="auth-submit__spinner" aria-hidden="true" />
      )}
      <span className={loading ? 'auth-submit__text--loading' : ''}>
        {children}
      </span>
    </button>
  );
};

export default SubmitButton;
