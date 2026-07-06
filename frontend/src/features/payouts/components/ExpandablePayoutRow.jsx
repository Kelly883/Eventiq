import React, { useState } from 'react';
import { formatCurrency, formatPayoutMethod } from '../utils';
import StatusBadges from './StatusBadges';

const ExpandablePayoutRow = ({ payout }) => {
  const [isExpanded, setIsExpanded] = useState(false);

  return (
    <>
      <tr onClick={() => setIsExpanded(!isExpanded)} className="hover:bg-gray-50 cursor-pointer">
        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">#{payout.id}</td>
        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Event {payout.event_id}</td>
        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{formatCurrency(payout.amount)}</td>
        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
          <StatusBadges status={payout.status} />
        </td>
        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
          {new Date(payout.created_at).toLocaleDateString()}
        </td>
        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
          {isExpanded ? '▼' : '▶'}
        </td>
      </tr>
      {isExpanded && (
        <tr>
          <td colSpan={6} className="px-6 py-4 bg-gray-50">
            <div className="grid grid-cols-2 gap-4">
              <div>
                <p className="text-sm text-gray-500">Payout Method</p>
                <p className="text-sm font-medium">{formatPayoutMethod(payout.payout_method)}</p>
              </div>
              {payout.transaction_id && (
                <div>
                  <p className="text-sm text-gray-500">Transaction ID</p>
                  <p className="text-sm font-medium">{payout.transaction_id}</p>
                </div>
              )}
              {payout.processed_at && (
                <div>
                  <p className="text-sm text-gray-500">Processed At</p>
                  <p className="text-sm font-medium">{new Date(payout.processed_at).toLocaleString()}</p>
                </div>
              )}
            </div>
          </td>
        </tr>
      )}
    </>
  );
};

export default ExpandablePayoutRow;
