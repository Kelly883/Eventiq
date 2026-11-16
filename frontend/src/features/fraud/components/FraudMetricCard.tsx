import React from 'react';

interface FraudMetricCardProps {
  title: string;
  value: number | string;
  trend: 'up' | 'down' | 'neutral';
  trendPercent: number;
  icon: React.ReactNode;
  color: 'green' | 'amber' | 'red';
}

const FraudMetricCard: React.FC<FraudMetricCardProps> = ({
  title,
  value,
  trend,
  trendPercent,
  icon,
  color,
}) => {
  const trendColor = {
    up: 'emerald',
    down: 'rose',
    neutral: 'slate',
  }[trend];

  const trendIcon = {
    up: 'M6 9L15 15L9 21',
    down: 'M18 15L9 9L15 3',
    neutral: 'M9 10v2m4 0v2m-5-4h6m3-3a7 7 0 11-14 0 7 7 0 0114 0z',
  }[trend];

  return (
    <div
      className="bg-white rounded-xl p-6 shadow-sm border"
      aria-label={`${title}: ${String(value)}. Trend ${trend} by ${trendPercent}%`}
    >
      <div className="flex items-start gap-4">
        <div className="flex-shrink-0 w-12 h-12 rounded-lg flex items-center justify-center bg-${color}-50 border border-${color}-200" aria-hidden="true">
          {icon}
        </div>
        <div className="flex-1 min-w-0">
          <p className="text-sm font-medium text-slate-600 uppercase tracking-wider">{title}</p>
          <p className="text-3xl font-bold text-slate-900 mt-1">
            {String(value)}
          </p>
        </div>
        <div className="flex-shrink-0 ml-4">
          <svg
            className={`w-5 h-5 text-${trendColor}-500`}
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <path
              fill="none"
              stroke="currentColor"
              strokeWidth={2}
              d={trendIcon}
            />
          </svg>
          <span className={`text-xs font-medium text-${trendColor}-600 uppercase tracking-wider`}>
            {trend} {Math.abs(trendPercent)}%
          </span>
        </div>
      </div>
    </div>
  );
};

export { FraudMetricCard };