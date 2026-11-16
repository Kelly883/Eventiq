import React from 'react';

/**
 * MetricCard – A reusable card for displaying a single metric
 * (e.g., 'Tickets Delivered: 1,234').
 *
 * Takes a label, value, and optional trend indicator (up/down/neutral).
 *
 * Used on AdminDeliveryDashboardPage for summary metrics.
 */
interface MetricCardProps {
  /** Label/description of the metric */
  label: string;
  /** Value of the metric */
  value: string | number;
  /** Optional trend indicator */
  trend?: 'up' | 'down' | 'neutral';
  /** Optional className override */
  className?: string;
}

/** Get the trend SVG/icon */
const TrendIcon = ({
  trend,
  className,
}: {
  trend?: 'up' | 'down' | 'neutral';
  className?: string;
}) => {
  switch (trend) {
    case 'up':
      return (
        <svg
          className={`w-4 h-4 ${className} text-success`}
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          strokeWidth={2}
          strokeLinecap="round"
          strokeLinejoin="round"
        >
          <path d="M5 12h14M12 5l7 7-7 7" />
        </svg>
      );
    case 'down':
      return (
        <svg
          className={`w-4 h-4 ${className} text-error`}
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          strokeWidth={2}
          strokeLinecap="round"
          strokeLinejoin="round"
        >
          <path d="M12 20V10M5 12h14" />
        </svg>
      );
    case 'neutral':
    default:
      return null;
  }
};

/** Get the trend badge color classes */
const getTrendClasses = (trend?: 'up' | 'down' | 'neutral'): string => {
  switch (trend) {
    case 'up':
      return 'bg-success-100 text-success-800';
    case 'down':
      return 'bg-error-100 text-error-800';
    case 'neutral':
    default:
      return 'bg-slate-100 text-slate-600';
  }
};

export const MetricCard: React.FC<MetricCardProps> = ({
  label,
  value,
  trend,
  className,
}) => {
  const valueStr = typeof value === 'number' ? value.toLocaleString() : String(value);

  return (
    <div
      className={`bg-white border border-slate-100 rounded-xl p-6 md:p-8 shadow-sm ${className}`}
    >
      <div className="flex flex-col gap-2">
        <p className="text-slate-500 text-sm font-medium truncate">{label}</p>

        <div className="flex items-baseline gap-2">
          <span className="text-3xl font-bold text-slate-900">{valueStr}</span>

          {trend && (
            <div className={`flex items-center gap-1 p-2 rounded-md ${getTrendClasses(trend)}`}>
              <TrendIcon trend={trend} />
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default MetricCard;
