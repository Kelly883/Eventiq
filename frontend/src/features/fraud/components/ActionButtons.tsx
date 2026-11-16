import React from 'react';

interface ActionButtonsProps {
  status: 'pending' | 'reviewed' | 'resolved' | 'dismissed' | 'escalated';
  onApprove: () => void;
  onReject: () => void;
  isLoading: boolean;
  disabled?: boolean;
}

const ActionButtons: React.FC<ActionButtonsProps> = ({
  status,
  onApprove,
  onReject,
  isLoading,
  disabled,
}) => {
  const canApprove = status !== 'reviewed' && status !== 'resolved' && !disabled && !isLoading;
  const canReject = status !== 'reviewed' && status !== 'resolved' && !disabled && !isLoading;

  return (
    <div className="flex gap-2" aria-label="Action buttons">
      <button
        onClick={onApprove}
        disabled={!canApprove || isLoading}
        className="flex-1 rounded bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-300 focus:ring-offset-2 transition-colors"
        aria-label="Approve transaction"
        title="Approve"
      >
        {isLoading ? (
          <span className="align-middle animate-spin" />
        ) : (
          <span>Approve</span>
        )}
      </button>
      <button
        onClick={onReject}
        disabled={!canReject || isLoading}
        className="flex-1 rounded bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-2 transition-colors"
        aria-label="Reject transaction"
        title="Reject"
      >
        {isLoading ? (
          <span className="align-middle animate-spin" />
        ) : (
          <span>Reject</span>
        )}
      </button>
    </div>
  );
};

export { ActionButtons };