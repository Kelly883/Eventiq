import React, { useState, useEffect, useRef, useCallback } from 'react';
import './RolesComponents.css';

const FOCUSABLE_SELECTOR = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';

export interface PermissionRequestModalProps {
  permissions: Array<{ id?: string; name: string; description?: string }>;
  onSubmit: (data: { permissionId: string; reason?: string }) => void;
  isOpen?: boolean;
  onClose: () => void;
  isLoading?: boolean;
  error?: string;
  onRetry?: () => void;
}

const PermissionRequestModal: React.FC<PermissionRequestModalProps> = ({
  permissions = [],
  onSubmit,
  isOpen = false,
  onClose,
  isLoading = false,
  error,
  onRetry,
}) => {
  const [permissionId, setPermissionId] = useState('');
  const [reason, setReason] = useState('');
  const modalRef = useRef<HTMLDivElement>(null);
  const previousActiveElement = useRef<HTMLElement | null>(null);

  const isInsideModal = useCallback(
    (element: HTMLElement | null): boolean => {
      if (!element || !modalRef.current) return false;
      return modalRef.current.contains(element);
    },
    []
  );

  useEffect(() => {
    if (!isOpen) {
      setPermissionId('');
      setReason('');
      return;
    }

    previousActiveElement.current = document.activeElement as HTMLElement | null;

    const handleEscape = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        onClose?.();
      }
    };

    const handleTab = (e: KeyboardEvent) => {
      if (e.key !== 'Tab') return;

      const modal = modalRef.current;
      if (!modal) return;

      const focusableElements = Array.from(modal.querySelectorAll<HTMLElement>(FOCUSABLE_SELECTOR));
      if (focusableElements.length === 0) return;

      const firstFocusable = focusableElements[0];
      const lastFocusable = focusableElements[focusableElements.length - 1];

      // If focus is not inside the modal, pull it back in
      if (!isInsideModal(document.activeElement as HTMLElement | null)) {
        e.preventDefault();
        firstFocusable?.focus();
        return;
      }

      if (e.shiftKey) {
        if (document.activeElement === firstFocusable) {
          e.preventDefault();
          lastFocusable?.focus();
        }
      } else {
        if (document.activeElement === lastFocusable) {
          e.preventDefault();
          firstFocusable?.focus();
        }
      }
    };

    const modalElement = modalRef.current;
    modalElement?.addEventListener('keydown', handleEscape);
    modalElement?.addEventListener('keydown', handleTab);

    return () => {
      modalElement?.removeEventListener('keydown', handleEscape);
      modalElement?.removeEventListener('keydown', handleTab);
      previousActiveElement.current?.focus();
    };
  }, [isOpen, onClose, isInsideModal]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!onSubmit || !permissionId) return;
    onSubmit({ permissionId, reason: reason || undefined });
    setPermissionId('');
    setReason('');
  };

  if (!isOpen) {
    return null;
  }

  if (isLoading) {
    return (
      <div className="permission-modal__overlay" role="status" aria-label="Requesting permission" data-testid="permission-request-modal">
        <div className="permission-modal">
          <div className="permission-modal__header">
            <div className="permission-modal__skeleton-title" />
            <div className="permission-modal__skeleton-close" />
          </div>
          <div className="permission-modal__form">
            <div className="permission-modal__skeleton-field" />
            <div className="permission-modal__skeleton-field" />
            <div className="permission-modal__skeleton-actions" />
          </div>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="permission-modal__overlay" role="alert" data-testid="permission-request-modal">
        <div className="permission-modal">
          <div className="permission-modal__header">
            <h2 id="permission-modal-title" className="permission-modal__title">Request Elevated Permissions</h2>
            <button type="button" onClick={onClose} className="permission-modal__close" aria-label="Close" data-testid="permission-modal-close">×</button>
          </div>
          <div className="permission-modal__form">
            <p className="permission-modal__error-message">{error}</p>
            {onRetry && (
              <button type="button" onClick={onRetry} className="permission-modal__retry" data-testid="permission-modal-retry">Retry</button>
            )}
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="permission-modal__overlay" role="dialog" aria-modal="true" aria-labelledby="permission-modal-title" data-testid="permission-request-modal">
      <div className="permission-modal" ref={modalRef}>
        <div className="permission-modal__header">
          <h2 id="permission-modal-title" className="permission-modal__title">
            Request Elevated Permissions
          </h2>
          <button
            type="button"
            onClick={onClose}
            className="permission-modal__close"
            aria-label="Close"
            disabled={isLoading}
            data-testid="permission-modal-close"
          >
            ×
          </button>
        </div>
        <form onSubmit={handleSubmit} className="permission-modal__form">
          <div className="permission-modal__field">
            <label htmlFor="permission-select" className="permission-modal__label">
              Permission
            </label>
            <select
              id="permission-select"
              value={permissionId}
              onChange={(e) => setPermissionId(e.target.value)}
              className="permission-modal__select"
              required
              data-testid="permission-select"
            >
              <option value="">Select a permission…</option>
              {permissions.map((perm) => (
                <option key={perm.id || perm.name} value={perm.id || perm.name}>
                  {perm.name}
                  {perm.description ? ` — ${perm.description}` : ''}
                </option>
              ))}
            </select>
          </div>
          <div className="permission-modal__field">
            <label htmlFor="permission-reason" className="permission-modal__label">
              Reason <span className="permission-modal__optional">(optional)</span>
            </label>
            <textarea
              id="permission-reason"
              value={reason}
              onChange={(e) => setReason(e.target.value)}
              placeholder="Why do you need this permission?"
              className="permission-modal__textarea"
              rows={3}
              maxLength={500}
              aria-describedby="permission-reason-count"
              data-testid="permission-reason"
            />
            <span id="permission-reason-count" className="permission-modal__char-count" aria-live="off">
              {reason.length}/500
            </span>
          </div>
          <div className="permission-modal__actions">
            <button type="button" onClick={onClose} className="permission-modal__cancel" disabled={isLoading} data-testid="permission-modal-cancel">Cancel</button>
            <button type="submit" disabled={!permissionId || isLoading} className="permission-modal__submit" data-testid="permission-modal-submit">
              {isLoading && <span className="permission-modal__spinner" aria-hidden="true" />}
              <span className={isLoading ? 'permission-modal__submit-text--loading' : ''}>{isLoading ? 'Requesting…' : 'Request'}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

PermissionRequestModal.defaultProps = {
  permissions: [],
  isOpen: false,
  isLoading: false,
  error: undefined,
  onRetry: undefined,
};

export default PermissionRequestModal;
