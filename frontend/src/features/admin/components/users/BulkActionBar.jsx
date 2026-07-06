import React from 'react';

export const BulkActionBar = ({ loading, onAction }) => {
  return (
    <div className="flex items-center gap-3">
      <button
        disabled={loading}
        onClick={() => onAction?.('approve')}
        className="px-3 py-1.5 rounded bg-gray-900 text-white text-sm disabled:opacity-50"
      >
        Approve selected
      </button>
      <button
        disabled={loading}
        onClick={() => onAction?.('reject')}
        className="px-3 py-1.5 rounded bg-white border text-gray-900 text-sm disabled:opacity-50"
      >
        Reject selected
      </button>
    </div>
  );
};

