import React from 'react';
import './AuthComponents.css';

const AuthCard = ({ children, title, gradient = false }) => {
  return (
    <div className={`auth-card ${gradient ? 'auth-card--gradient' : ''}`} data-testid="auth-card">
      {title && (
        <div className="auth-card__header">
          <h1 className="auth-card__title">{title}</h1>
        </div>
      )}
      <div className="auth-card__body">
        {children}
      </div>
    </div>
  );
};

export default AuthCard;
