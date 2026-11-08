import React from 'react';
import { LineChart, Line, ResponsiveContainer } from 'recharts';
import { useReducedMotion } from '@/features/dashboard/hooks/useReducedMotion';

/**
 * MetricCard displays a single KPI with an optional sparkline and a trend indicator.
 *
 * @param value            The primary value to show (string, number, or JSX). If omitted, a dash is displayed.
 * @param label            The label describing the metric.
 * @param trendDirection   One of "up", "down" or "neutral". Determines the colour of the trend badge.
 * @param trendPercentage  The percentage change (or any string) displayed next to the arrow.
 * @param loading          When true, renders a skeleton placeholder.
 * @param sparklineData    Optional array of numbers to render a tiny sparkline.
 */
interface SparklinePoint {
  value: number;
  index: number;
}

interface MetricCardProps {
  /** Primary display value; defaults to an em dash when missing */
  value?: React.ReactNode;
  /** Human‑readable label */
  label?: string;
  /** Trend direction – influences colour and arrow */
  trendDirection?: 'up' | 'down' | 'neutral';
  /** Percentage or label shown next to the arrow */
  trendPercentage?: string | number;
  /** Show loading skeleton */
  loading?: boolean;
  /** Numeric data for the sparkline; empty array means no sparkline */
  sparklineData?: number[];
}

const DIRECTION_ARIA = { up: 'up', down: 'down', neutral: 'flat' };

const Sparkline = ({ data = [] }: { data?: SparklinePoint[] }) => {
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

interface TrendIndicatorProps {
  direction?: MetricCardProps['trendDirection'];
  percentage?: MetricCardProps['trendPercentage'];
}

/**
 * Displays an arrow and coloured badge based on the trend direction.
 * Hides the badge when direction is neutral and no explicit percentage is provided.
 */
const TrendIndicator = ({ direction, percentage }: TrendIndicatorProps) => {
  if (direction === 'neutral' && (percentage === undefined || percentage === null || percentage === '')) {
    return null;
  }
  const isUp = direction === 'up';
  const isDown = direction === 'down';
  const dir = isUp ? 'up' : isDown ? 'down' : 'neutral';
  const arrow = isUp ? '↑' : isDown ? '↓' : '→';
  const trendValue = percentage == null || percentage === '' ? '0%' : String(percentage);

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
      aria-label={`Trending ${DIRECTION_ARIA[dir]} by ${trendValue}`}
    >
      <span aria-hidden="true">{arrow}</span> {trendValue}
    </span>
  );
};

const defaultValue = '—';
const defaultLabel = 'Metric';

const CardSkeleton = ({ reducedMotion }: { reducedMotion?: boolean }) => (
  <div className="w-full space-y-3" aria-hidden="true">
    <div className={`h-3 w-1/2 rounded bg-slate-100 ${reducedMotion ? '' : 'animate-pulse'}`} />
    <div className={`h-7 w-3/4 rounded bg-slate-100 ${reducedMotion ? '' : 'animate-pulse'}`} />
    <div className={`h-5 w-16 rounded-full bg-slate-100 ${reducedMotion ? '' : 'animate-pulse'}`} />
  </div>
);

const MetricCard = ({
  value,
  label,
  trendDirection,
  trendPercentage,
  loading,
  sparklineData,
}: MetricCardProps) => {
  const sparkData = Array.isArray(sparklineData) ? sparklineData.map((v, i) => ({ value: v, index: i })) : [];
  const reducedMotion = useReducedMotion();
  const displayValue = value === null || value === undefined || value === '' ? defaultValue : value;

  return (
    <div
      className="bg-white border border-slate-100 rounded-xl p-6 shadow-sm flex flex-col items-start gap-3 min-w-0"
      role="group"
      aria-label={label || defaultLabel}
      aria-busy={loading ? 'true' : 'false'}
    >
      {loading ? (
        <CardSkeleton reducedMotion={reducedMotion} />
      ) : (
        <>
          <p className="text-xs font-semibold text-slate-400 uppercase tracking-wider truncate w-full">
            {label || defaultLabel}
          </p>
          <div className="w-full flex items-end justify-between gap-2">
            <h3 className="text-2xl font-bold text-slate-800 truncate">{displayValue}</h3>
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
