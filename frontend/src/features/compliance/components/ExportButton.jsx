import React from 'react';

const ExportButton = ({ filters, onExport, loading }) => {
  const handleClick = () => {
    if (typeof onExport === 'function') {
      onExport(filters);
    }
  };

  return (
    <button
      className="px-3 py-2 rounded bg-white border border-gray-300 text-gray-900 text-sm hover:bg-gray-50 disabled:opacity-50"
      onClick={handleClick}
      disabled={loading}
    >
      Export
    </button>
  );
};

export { ExportButton };
