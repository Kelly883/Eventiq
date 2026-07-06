import React from 'react';

export const ReconciliationReport = ({ loading, report }) => {
  if (loading) return <div className="animate-pulse h-20 bg-gray-100 rounded" />;
  return (
    <div>
      <div className="text-sm font-semibold text-gray-900">Reconciliation Report</div>
      <div className="text-sm text-gray-500 mt-1">{report ? '' : '—'}</div>
    </div>
  );
};

