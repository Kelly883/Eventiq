import React from 'react';

interface RiskBadgeProps {
  riskLevel: 'low' | 'medium' | 'high';
  riskScore: number;
}

const RiskBadge: React.FC<RiskBadgeProps> = ({ riskLevel, riskScore }) => {
  const riskColors = {
    low: { bg: 'green-100', text: 'green-800', border: 'green-200' },
    medium: { bg: 'amber-100', text: 'amber-800', border: 'amber-200' },
    high: { bg: 'red-100', text: 'red-800', border: 'red-200' },
  }[riskLevel];

  return (
    <span
      className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${riskColors.border} border border-${riskColors.border} bg-${riskColors.bg} text-${riskColors.text}`}
      aria-label={`Risk level: ${riskLevel}, score: ${riskScore}`}
    >
      {riskLevel[0].toUpperCase() + riskLevel.slice(1)}
      <span className="ml-1">({riskScore})</span>
    </span>
  );
};

export { RiskBadge };