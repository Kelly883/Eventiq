import React from 'react';
import { LineChart, Line, ResponsiveContainer } from 'recharts';

const DIRECTION_ARIA = { up: 'up', down: 'down', neutral: 'flat' };

const Sparkline = ({ data = [] }) => {
  if (!data || data.length === 0) return null;
  return (
    <div className="w-16 h-8">
      <ResponsiveContainer width="100%" height="100%">
        <LineChart data={data}>
          <Line type="monotone" dataKey="value" stroke="#6366f1" strokeWidth={1.5} dot={false} />
        </LineChart>
      </ResponsiveContainer>
    </div>
  );
};

const TrendIndicator = ({ direction, percentage }) => {
  const isUp = direction === 'up';
  const isDown = direction === 'down';
  const dir = isUp ? 'up' : isDown ? 'down' : 'neutral';
  const arrow = isUp ? '↑' : isDown ? '↓' : '→';

  return (
    <span
      className={`inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs font-semibold ${
        isUp
          ? 'text-emerald-700 bg-emerald-50 border-emerald-100'
          : isDown
            ? 'text-rose-700 bg-rose-50 border-rose-100'
            : 'text-slate-500 bg-slate-100 border-slate-200'
      }`}
      aria-live="polite"
      aria-label={`Trending ${DIRECTION_ARIA[dir]} by ${percentage || '0%'}`}
    >
      <span aria-hidden="true">{arrow}</span> {percentage || '0%'}
    </span>
  );
};

const defaultValue = '—';
const defaultLabel = 'Metric';

const CardSkeleton = () => (
  <div className="w-full space-y-3" aria-hidden="true">
    <div className="h-3 w-1/2 rounded bg-slate-100 animate-pulse" />
    <div className="h-7 w-3/4 rounded bg-slate-100 animate-pulse" />
    <div className="h-5 w-16 rounded-full bg-slate-100 animate-pulse" />
  </div>
);

const MetricCard = ({
  value,
  label,
  trendDirection,
  trendPercentage,
  loading,
  sparklineData,
  ...rest
}) => {
  const sparkData = Array.isArray(sparklineData)
    ? sparklineData.map((v, i) => ({ value: v, index: i }))
    : undefined;

  return (
    <div
      className="bg-white border border-slate-100 rounded-xl p-6 shadow-sm flex flex-col items-start gap-3 min-w-0"
      role="group"
      aria-label={label || defaultLabel}
      aria-busy={loading ? 'true' : 'false'}
    >
      {loading ? (
        <CardSkeleton />
      ) : (
        <>
          <p className="text-xs font-semibold text-slate-400 uppercase tracking-wider truncate w-full">
            {label || defaultLabel}
          </p>
          <div className="w-full flex items-end justify-between gap-2">
            <h3 className="text-2xl font-bold text-slate-800 truncate">{value || defaultValue}</h3>
            <Sparkline data={sparkData} />
          </div>
          <TrendIndicator direction={trendDirection} percentage={trendPercentage} />
        </>
      )}
    </div>
  );
};

MetricCard.displayName = 'MetricCard';

export default MetricCard;
