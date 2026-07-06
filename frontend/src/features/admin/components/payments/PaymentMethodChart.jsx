import React from 'react';

export const PaymentMethodChart = ({ loading, chart }) => {
  if (loading) return <div className="animate-pulse h-32 bg-gray-100 rounded" />;
  return (
    <div>
      <div className="text-sm font-semibold text-gray-900">Payment Method</div>
      <div className="text-sm text-gray-500 mt-1">{chart ? '' : '—'}</div>
    </div>
  );
};

