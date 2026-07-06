import React from 'react';
import { formatCurrency } from '../utils';

const SummaryCards = ({ summary }) => {
  if (!summary) return null;

  const cards = [
    { label: 'Total Earned', value: summary.total_earned, color: 'bg-green-50' },
    { label: 'Pending', value: summary.total_pending, color: 'bg-yellow-50' },
    { label: 'Processed', value: summary.total_processed, color: 'bg-blue-50' },
    { label: 'Next Payout', value: summary.next_payout, color: 'bg-purple-50' },
  ];

  return (
    <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
      {cards.map((card, index) => (
        <div key={index} className={`p-6 rounded-lg ${card.color}`}>
          <div className="text-sm font-medium text-gray-500">{card.label}</div>
          <div className="text-2xl font-bold text-gray-900">{formatCurrency(card.value)}</div>
        </div>
      ))}
    </div>
  );
};

export default SummaryCards;
