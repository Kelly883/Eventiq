import React from 'react';

const PayoutTable = ({ payouts, loading, onRowClick }) => {
  if (loading) {
    return <div>Loading payouts...</div>;
  }

  return (
    <table className="min-w-full divide-y divide-gray-200">
      <thead className="bg-gray-50">
        <tr>
          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
        </tr>
      </thead>
      <tbody className="bg-white divide-y divide-gray-200">
        {payouts.map((payout) => (
          <tr 
            key={payout.id} 
            onClick={() => onRowClick?.(payout)}
            className="hover:bg-gray-50 cursor-pointer"
          >
            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">#{payout.id}</td>
            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Event {payout.event_id}</td>
            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${payout.amount.toFixed(2)}</td>
            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{payout.status}</td>
            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{new Date(payout.created_at).toLocaleDateString()}</td>
          </tr>
        ))}
      </tbody>
    </table>
  );
};

export default PayoutTable;
