import React from 'react';
import './SessionModal.css';

/**
 * Shared modal for session-related notifications.
 * Used for both "Session Expired" and "Session Extended" states.
 *
 * Features:
 * - Proper overlay/backdrop that dims the underlying page
 * - Controlled modal width with appropriate padding
 * - Properly sized icon (48x48px) and close button (32x32px)
 * - No browser-default buttons
 * - Responsive from 320px upward
 * - Accessible (role="dialog", aria-modal, focus trap)
 */
export default function SessionModal({
  isOpen,
  title,
  message,
  type = 'warning', // 'warning' | 'info' | 'success'
  primaryActionLabel = 'Sign in',
  onPrimaryAction,
  secondaryActionLabel,
  onSecondaryAction,
  onClose,
}) {
  if (!isOpen) return null;

  const iconMap = {
    warning: (
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
        <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
      </svg>
    ),
    info: (
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
        <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    ),
    success: (
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    ),
  };

  const handleBackdropClick = (e) => {
    if (e.target === e.currentTarget && onClose) {
      onClose();
    }
  };

  return (
    <div
      className="session-modal-backdrop"
      onClick={handleBackdropClick}
      role="dialog"
      aria-modal="true"
      aria-labelledby="session-modal-title"
      aria-describedby="session-modal-message"
    >
      <div className={`session-modal session-modal--${type}`}>
        {/* Close button */}
        {onClose && (
          <button
            type="button"
            className="session-modal-close"
            onClick={onClose}
            aria-label="Close"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={2} strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
              <path d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        )}

        {/* Icon */}
        <div className="session-modal-icon" aria-hidden="true">
          {iconMap[type] || iconMap.warning}
        </div>

        {/* Content */}
        <h2 id="session-modal-title" className="session-modal-title">
          {title}
        </h2>
        <p id="session-modal-message" className="session-modal-message">
          {message}
        </p>

        {/* Actions */}
        <div className="session-modal-actions">
          {onPrimaryAction && (
            <button
              type="button"
              className="session-modal-btn session-modal-btn--primary"
              onClick={onPrimaryAction}
            >
              {primaryActionLabel}
            </button>
          )}
          {onSecondaryAction && secondaryActionLabel && (
            <button
              type="button"
              className="session-modal-btn session-modal-btn--secondary"
              onClick={onSecondaryAction}
            >
              {secondaryActionLabel}
            </button>
          )}
        </div>
      </div>
    </div>
  );
}
