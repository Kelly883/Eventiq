import React from 'react';
import './AuthComponents.css';

const SubmitButton = ({ loading = false, disabled = false, children, type = 'submit', className = '', variant = 'primary', ...props }) => {
  return (
    <button
      type={type}
      disabled={disabled || loading}
      className={`auth-submit ${className} ${variant === 'success' ? 'auth-submit--success' : ''}`.trim()}
      {...props}
      data-testid="submit-button"
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
