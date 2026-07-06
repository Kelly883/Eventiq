import React from 'react';

const BulkActionBar = ({ loading, selectedCount, onAction }) => {
  if (!selectedCount) {
    return <div className="text-sm text-gray-500">Select logs to bulk update</div>;
  }

  return (
    <div className="flex items-center gap-3">
      <div className="text-sm text-gray-700">{selectedCount} selected</div>
      <button
        disabled={loading}
        className="px-3 py-1.5 rounded bg-gray-900 text-white text-sm disabled:opacity-50"
        onClick={() => onAction?.('tag')}
      >
        Tag
      </button>
      <button
        disabled={loading}
        className="px-3 py-1.5 rounded bg-white border text-gray-900 text-sm disabled:opacity-50"
        onClick={() => onAction?.('export')}
      >
        Export selected
      </button>
    </div>
  );
};

export { BulkActionBar };

