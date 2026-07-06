import React from 'react';

export const MetricCard = ({ loading, data }) => {
  if (loading) return <div className="animate-pulse h-24 bg-gray-100 rounded" />;
  return (
    <div className="p-4 rounded bg-gray-50 border">
      <div className="text-sm text-gray-500">Metrics</div>
      <div className="mt-2 text-2xl font-bold text-gray-900">{data ? '—' : '—'}</div>
    </div>
  );
};

