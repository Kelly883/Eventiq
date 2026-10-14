import React, { useEffect, useRef } from 'react';

const DeleteConfirmationModal = ({ isOpen, onClose, onConfirm, title = 'Delete confirmation', message = 'Are you sure you want to delete this item?', confirmLabel = 'Delete', cancelLabel = 'Cancel', isLoading = false }) => {
  const modalRef = useRef(null);
  const previousActiveElement = useRef(null);

  useEffect(() => {
    if (!isOpen) return;

    previousActiveElement.current = document.activeElement;

    const handleKeyDown = (e) => {
      if (e.key === 'Escape') {
        e.preventDefault();
        onClose?.();
      }
    };

    const modal = modalRef.current;
    if (!modal) return;

    const focusableSelector = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';
    const focusableElements = Array.from(modal.querySelectorAll<HTMLElement>(focusableSelector));
    if (focusableElements.length > 0) {
      focusableElements[0].focus();
    }

    const handleTab = (e) => {
      if (e.key !== 'Tab') return;
      const firstFocusable = focusableElements[0];
      const lastFocusable = focusableElements[focusableElements.length - 1];

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

    modal.addEventListener('keydown', handleKeyDown);
    modal.addEventListener('keydown', handleTab);

    return () => {
      modal.removeEventListener('keydown', handleKeyDown);
      modal.removeEventListener('keydown', handleTab);
      previousActiveElement.current?.focus();
    };
  }, [isOpen, onClose]);

  if (!isOpen) return null;

  return (
    <div className="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title">
      <div className="modal-content" ref={modalRef}>
        <h2 id="delete-modal-title" className="modal-title">{title}</h2>
        <p className="modal-message">{message}</p>
        <div className="modal-actions">
          <button type="button" onClick={onClose} disabled={isLoading} className="modal-cancel">
            {cancelLabel}
          </button>
          <button type="button" onClick={onConfirm} disabled={isLoading} className="modal-confirm modal-confirm--danger">
            {isLoading ? 'Deleting...' : confirmLabel}
          </button>
        </div>
      </div>
    </div>
  );
};

export default DeleteConfirmationModal;
