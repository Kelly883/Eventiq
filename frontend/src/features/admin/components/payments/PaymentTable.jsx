import React from 'react';

export const PaymentTable = ({ loading, payments, onRowClick }) => {
  if (loading) return <div className="animate-pulse h-48 bg-gray-100 rounded" />;
  return (
    <table className="w-full text-sm">
      <thead>
        <tr className="text-left text-gray-500 border-b">
          <th className="py-2 px-2">Payment</th>
          <th className="py-2 px-2">Amount</th>
        </tr>
      </thead>
      <tbody>
        {(payments || []).length === 0 ? (
          <tr>
            <td colSpan={2} className="py-6 px-2 text-gray-500">
              No payments
            </td>
          </tr>
        ) : (
          payments.map((p) => (
            <tr
              key={p.id}
              className="border-b hover:bg-gray-50 cursor-pointer"
              onClick={() => onRowClick?.(p)}
            >
              <td className="py-2 px-2 font-medium text-gray-900">{p.reference ?? '—'}</td>
              <td className="py-2 px-2 text-gray-600">{p.amount ?? '—'}</td>
            </tr>
          ))
        )}
      </tbody>
    </table>
  );
};

