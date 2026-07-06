import React from 'react';

export const PaymentDetailsPanel = ({ payment, loading }) => {
  if (loading) return <div className="animate-pulse h-40 bg-gray-100 rounded" />;
  if (!payment) return <div className="text-sm text-gray-500">Select a payment</div>;

  return (
    <div className="p-4 border rounded">
      <div className="font-semibold text-gray-900">{payment.reference ?? 'Payment'}</div>
      <div className="text-sm text-gray-600 mt-1">Amount: {payment.amount ?? '—'}</div>
      <div className="text-sm text-gray-600 mt-1">Method: {payment.method ?? '—'}</div>
    </div>
  );
};

