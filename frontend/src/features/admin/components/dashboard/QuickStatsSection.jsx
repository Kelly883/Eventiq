import React from 'react';

export const QuickStatsSection = ({ loading, data }) => {
  if (loading) return <div className="animate-pulse h-20 bg-gray-100 rounded" />;
  return (
    <div className="p-4 rounded bg-gray-50 border">
      <div className="text-sm text-gray-500">Quick Stats</div>
      <div className="mt-1 text-gray-900 font-semibold">{data ? '' : '—'}</div>
    </div>
  );
};

