import React from 'react';
import './AuthComponents.css';

/**
 * AuthCard is a centered container with optional gradient background and
 * skeleton loading state.
 */
const AuthCard = ({ children, title, gradient = false, loading = false, dataTestId }) => {
  return (
    <div
      className={`auth-card ${gradient ? 'auth-card--gradient' : ''} ${loading ? 'auth-card--loading' : ''}`}
      data-testid={dataTestId || 'auth-card'}
      aria-busy={loading || undefined}
    >
      {title && !loading && (
        <div className="auth-card__header">
          <h1 className="auth-card__title">{title}</h1>
        </div>
      )}
      <div className="auth-card__body">
        {loading ? (
          <div className="auth-card__skeleton" role="status" aria-live="polite" aria-label="Loading content">
            <div className="auth-card__skeleton-line auth-card__skeleton-line--title" />
            <div className="auth-card__skeleton-line" />
            <div className="auth-card__skeleton-line" />
            <div className="auth-card__skeleton-line auth-card__skeleton-line--short" />
            <div className="auth-card__skeleton-line auth-card__skeleton-line--button" />
          </div>
        ) : (
          children
        )}
      </div>
    </div>
  );
};

AuthCard.defaultProps = {
  title: undefined,
  gradient: false,
  loading: false,
  dataTestId: undefined,
};

export default AuthCard;
