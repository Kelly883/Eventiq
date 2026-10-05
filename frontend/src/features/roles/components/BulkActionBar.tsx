import React, { useState } from 'react';
import type { OptimisticUpdate } from '../types';
import './RolesComponents.css';

export interface BulkActionBarProps {
  selectedCount: number;
  onApply: (data: {
    roleId: string;
    reason?: string;
    optimisticIds?: string[];
    expectedUpdatedAt?: string;
  }) => void;
  onCancel?: () => void;
  isLoading?: boolean;
  optimisticUpdates?: OptimisticUpdate[];
  error?: string;
  onRetry?: () => void;
  conflictMessage?: string;
}

const BulkActionBar: React.FC<BulkActionBarProps> = ({
  selectedCount = 0,
  onApply,
  onCancel,
  isLoading = false,
  optimisticUpdates = [],
  error,
  onRetry,
  conflictMessage,
}) => {
  const [roleId, setRoleId] = useState('');
  const [reason, setReason] = useState('');
  const [submitted, setSubmitted] = useState(false);

  if (selectedCount === 0) {
    return null;
  }

  if (isLoading && submitted) {
    return (
      <div className="bulk-action-bar" role="status" aria-label="Applying role changes" data-testid="bulk-action-bar">
        <div className="bulk-action-bar__skeleton">
          <div className="bulk-action-bar__skeleton-line" />
          <div className="bulk-action-bar__skeleton-line bulk-action-bar__skeleton-line--short" />
        </div>
      </div>
    );
  }

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!onApply || !roleId) return;
    setSubmitted(true);

    const optimisticIds = optimisticUpdates.map((u) => u.userId);
    onApply({
      roleId,
      reason: reason || undefined,
      optimisticIds,
    });
    setRoleId('');
    setReason('');
  };

  return (
    <div aria-live="polite" aria-atomic="true">
      <div
        className="bulk-action-bar"
        role="toolbar"
        aria-label={`${selectedCount} users selected`}
        data-testid="bulk-action-bar"
      >
        {conflictMessage && (
          <div className="bulk-action-bar__conflict" role="alert" data-testid="bulk-action-conflict">
            <span className="bulk-action-bar__conflict-text">{conflictMessage}</span>
            {onRetry && (
              <button type="button" onClick={onRetry} className="bulk-action-bar__retry" data-testid="bulk-action-retry">
                Retry
              </button>
            )}
          </div>
        )}
        {error && !conflictMessage && (
          <div className="bulk-action-bar__error" role="alert" data-testid="bulk-action-error">
            <span className="bulk-action-bar__error-text">{error}</span>
            {onRetry && (
              <button type="button" onClick={onRetry} className="bulk-action-bar__retry" data-testid="bulk-action-retry">
                Retry
              </button>
            )}
          </div>
        )}
        <form className="bulk-action-bar__form" onSubmit={handleSubmit}>
          <span className="bulk-action-bar__count" data-testid="bulk-action-count">
            {selectedCount} user{selectedCount !== 1 ? 's' : ''} selected
          </span>
          <label className="bulk-action-bar__select-label" htmlFor="bulk-action-role-select">
            Role
          </label>
          <select
            id="bulk-action-role-select"
            value={roleId}
            onChange={(e) => setRoleId(e.target.value)}
            className="bulk-action-bar__select"
            data-testid="bulk-action-role-select"
          >
            <option value="">Assign role…</option>
            <option value="admin">Admin</option>
            <option value="organizer">Organizer</option>
            <option value="attendee">Attendee</option>
            <option value="support">Support</option>
          </select>
          <label className="bulk-action-bar__reason-label" htmlFor="bulk-action-reason">
            Reason <span className="bulk-action-bar__optional">(optional)</span>
          </label>
          <input
            id="bulk-action-reason"
            type="text"
            value={reason}
            onChange={(e) => setReason(e.target.value)}
            placeholder="Reason (optional)"
            className="bulk-action-bar__reason"
            maxLength={500}
            data-testid="bulk-action-reason"
          />
          <span className="bulk-action-bar__char-count" aria-live="off">
            {reason.length}/500
          </span>
          <button
            type="submit"
            disabled={!roleId || isLoading}
            className="bulk-action-bar__apply"
            data-testid="bulk-action-apply"
          >
            {isLoading && <span className="bulk-action-bar__spinner" aria-hidden="true" />}
            <span className={isLoading ? 'bulk-action-bar__apply-text--loading' : ''}>
              {isLoading ? 'Applying…' : 'Apply'}
            </span>
          </button>
          <button
            type="button"
            onClick={onCancel}
            className="bulk-action-bar__cancel"
            disabled={isLoading}
            data-testid="bulk-action-cancel"
          >
            Cancel
          </button>
        </form>
        <span
          aria-live="polite"
          aria-atomic="true"
          className="bulk-action-bar__sr-only"
        >
          {isLoading ? 'Applying role changes' : selectedCount ? `${selectedCount} users selected` : ''}
        </span>
      </div>
    </div>
  );
};

export default BulkActionBar;
