import React, { useState, useEffect } from 'react';
import { addToastListener } from '../../../lib/api';
import './ToastContainer.css';

/**
 * Toast notifications for EventIQ.
 *
 * Replaces the previous Tailwind-based implementation (which rendered as raw
 * unstyled HTML because Tailwind is not installed/configured in this project).
 * Now uses the EventIQ design system tokens from dashboard.css.
 */
export default function ToastContainer() {
  const [toasts, setToasts] = useState([]);

  useEffect(() => {
    const removeListener = addToastListener((newToast) => {
      // Handle clear action
      if (newToast.type === 'clear' && newToast.id === -1) {
        setToasts([]);
        return;
      }

      setToasts((prev) => {
        // Deduplicate: skip if an identical toast (same title, description, type)
        // is already visible. Prevents stacked toasts from StrictMode
        // double-invokes or rapid re-renders.
        const isDuplicate = prev.some(
          (t) => t.title === newToast.title && t.description === newToast.description && t.type === newToast.type
        );
        if (isDuplicate) return prev;
        return [...prev, newToast];
      });

      // Auto-dismiss logic
      const duration = newToast.duration ?? 5000;
      setTimeout(() => {
        setToasts((prev) => prev.filter((t) => t.id !== newToast.id));
      }, duration);
    });

    return () => {
      removeListener();
    };
  }, []);

  const removeToast = (id) => {
    setToasts((prev) => prev.filter((t) => t.id !== id));
  };

  if (toasts.length === 0) return null;

  return (
    <div id="global-toast-container">
      {toasts.map((toast) => (
        <ToastItem key={toast.id} toast={toast} onDismiss={removeToast} />
      ))}
    </div>
  );
}

/**
 * Single toast notification item.
 * Renders icon, title, description, and close button.
 */
function ToastItem({ toast, onDismiss }) {
  const typeClass = {
    error: 'toast-item--error',
    warning: 'toast-item--warning',
    success: 'toast-item--success',
  }[toast.type] || 'toast-item--info';

  return (
    <div
      className={`toast-item ${typeClass}`}
      role="alert"
      aria-live="polite"
    >
      <div className="toast-row">
        {/* Icon */}
        <div className="toast-icon" aria-hidden="true">
          <ToastIcon type={toast.type} />
        </div>

        {/* Content */}
        <div className="toast-body">
          <p className="toast-title">{toast.title}</p>
          {toast.description && (
            <p className="toast-description">{toast.description}</p>
          )}
        </div>

        {/* Close button */}
        <button
          type="button"
          onClick={() => onDismiss(toast.id)}
          className="toast-close"
          aria-label="Dismiss notification"
        >
          <svg
            className="toast-close-icon"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            strokeWidth={2}
            strokeLinecap="round"
            strokeLinejoin="round"
          >
            <path d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>
  );
}

/**
 * Inline SVG icon per toast type.
 * Sized via CSS (20x20px) to match design system spacing.
 */
function ToastIcon({ type }) {
  const common = {
    width: 20,
    height: 20,
    fill: 'none',
    viewBox: '0 0 24 24',
    stroke: 'currentColor',
    strokeWidth: 2,
    strokeLinecap: 'round',
    strokeLinejoin: 'round',
  };

  switch (type) {
    case 'error':
      return (
        <svg {...common} aria-hidden="true">
          <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
      );
    case 'warning':
      return (
        <svg {...common} aria-hidden="true">
          <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
      );
    case 'success':
      return (
        <svg {...common} aria-hidden="true">
          <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      );
    default: // info
      return (
        <svg {...common} aria-hidden="true">
          <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      );
  }
}
