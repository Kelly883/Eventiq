import React from 'react';
import { payoutService } from '../services';

const ExportButton = ({ filters }) => {
  const handleExport = async () => {
    try {
      await payoutService.exportPayouts(filters);
    } catch (err) {
      console.error('Export failed:', err);
    }
  };

  return (
    <button
      onClick={handleExport}
      className="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700"
    >
      Export
    </button>
  );
};

export default ExportButton;
