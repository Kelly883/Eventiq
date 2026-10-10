import React from 'react';

const TrendIndicator = ({ direction, percentage }) => {
  const isUp = direction === 'up';
  const isDown = direction === 'down';

  return (
    <span
      className={`inline-flex items-center gap-1 rounded text-xs font-medium ${
        isUp ? 'text-emerald-600 bg-emerald-100' : isDown ? 'text-rose-600 bg-rose-100' : 'text-slate-400 bg-slate-100'
      } py-0.5 px-2`}
    >
      {direction === 'up' ? '↑' : direction === 'down' ? '↓' : '→'} {percentage || '0%'}
    </span>
  );
};

const MetricCard = ({ value, label, trendDirection, trendPercentage }) => {
  return (
    <div className="bg-white border border-slate-100 rounded-xl p-6 shadow-sm flex flex-col items-start gap-3">
      <p className="text-xs font-semibold text-slate-400 uppercase tracking-wider">{label}</p>
      <h3 className="text-2xl font-bold text-slate-800">{value}</h3>
      <TrendIndicator direction={trendDirection} percentage={trendPercentage} />
    </div>
  );
};

export default MetricCard;