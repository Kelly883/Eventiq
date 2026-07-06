import React from 'react';

const ExportButton = ({ filters, onExport }) => {
  return (
    <button
      className="px-3 py-2 rounded bg-white border text-gray-900 text-sm"
      onClick={() => onExport?.(filters)}
    >
      Export
    </button>
  );
};

export { ExportButton };

