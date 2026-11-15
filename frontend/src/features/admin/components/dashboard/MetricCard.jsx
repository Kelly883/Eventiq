import React from 'react';
import PropTypes from 'prop-types';
import { TrendingUp, TrendingDown, Minus } from 'lucide-react';

const TREND_STYLES = {
  up: 'text-green-600 bg-green-50 border-green-100',
  down: 'text-rose-600 bg-rose-50 border-rose-100',
  neutral: 'text-slate-500 bg-slate-50 border-slate-100',
};

const TREND_ICONS = {
  up: <TrendingUp className="w-3 h-3" />,
  down: <TrendingDown className="w-3 h-3" />,
  neutral: <Minus className="w-3 h-3" />,
};

export const MetricCard = ({
  label = 'Metrics',
  value = '—',
  trend,
  trendLabel,
  loading = false,
}) => {
  if (loading) {
    return (
      <div className="h-24 rounded-lg bg-gray-100 dark:bg-slate-800 animate-pulse">
        <div className="h-4 w-24 mx-auto mt-6 bg-gray-200 dark:bg-slate-700 rounded" />
        <div className="h-8 w-3/4 mx-auto mt-4 bg-gray-200 dark:bg-slate-700 rounded" />
      </div>
    );
  }

  const hasTrend = trend !== undefined && trend !== null;
  const trendStyle = hasTrend
    ? TREND_STYLES[trend] || TREND_STYLES.neutral
    : TREND_STYLES.neutral;
  const trendIcon = hasTrend ? TREND_ICONS[trend] || TREND_ICONS.neutral : null;

  return (
    <div className="p-5 rounded-xl border border-gray-100 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm">
      <div className="flex items-center justify-between">
        <div>
          <p className="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
            {label}
          </p>
          <div className="mt-1 flex items-baseline gap-1">
            <span className="text-2xl font-bold text-gray-900 dark:text-slate-100">{value}</span>
          </div>
        </div>
        <div className="ml-4 flex flex-col justify-center h-full">
          {hasTrend && trendIcon ? (
            <span className={`flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium border ${trendStyle}`}>
              {trendIcon}
              <span>{trendLabel ?? Math.abs(trend)}%</span>
            </span>
          ) : null}
        </div>
      </div>
    </div>
  );
};

MetricCard.displayName = 'MetricCard';

MetricCard.propTypes = {
  label: PropTypes.string,
  value: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
  trend: PropTypes.oneOf(['up', 'down', 'neutral']),
  trendLabel: PropTypes.string,
  loading: PropTypes.bool,
};

MetricCard.defaultProps = {
  label: 'Metrics',
  value: '—',
  trend: null,
  trendLabel: null,
  loading: false,
};

