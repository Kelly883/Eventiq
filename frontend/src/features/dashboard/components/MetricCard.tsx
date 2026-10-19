import { useDashboardMetrics, DashboardMetrics } from '../hooks/useDashboardMetrics';
import BaseMetricCard from '@/features/analytics/components/MetricCard';

interface TrendData {
  direction: 'up' | 'down' | 'neutral';
  percentage: string;
}

type TrendKey = 'revenueTrend' | 'eventsTrend' | 'attendeesTrend' | 'ticketsTrend';

interface MetricCardConfig {
  key: Exclude<keyof DashboardMetrics, TrendKey>;
  label: string;
  trendKey: TrendKey;
  format?: (value: number) => string;
}

const formatCurrency = (value: number): string => {
  return new Intl.NumberFormat('en-NG', {
    style: 'currency',
    currency: 'NGN',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value);
};

const formatNumber = (value: number): string => {
  return new Intl.NumberFormat('en-US').format(value);
};

const METRIC_CONFIGS: MetricCardConfig[] = [
  { key: 'totalRevenue', label: 'Total Revenue', trendKey: 'revenueTrend', format: formatCurrency },
  { key: 'totalEvents', label: 'Active Events', trendKey: 'eventsTrend', format: formatNumber },
  { key: 'totalAttendees', label: 'Total Attendees', trendKey: 'attendeesTrend', format: formatNumber },
  { key: 'totalTicketsSold', label: 'Tickets Sold', trendKey: 'ticketsTrend', format: formatNumber },
];

export function MetricCardRow() {
  const { data: metrics, isLoading, error } = useDashboardMetrics();

  if (error) {
    return (
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" role="alert">
        {METRIC_CONFIGS.map((config) => (
          <div key={config.key} className="bg-white border border-rose-200 rounded-xl p-6 shadow-sm">
            <p className="text-xs font-semibold text-rose-400 uppercase tracking-wider">{config.label}</p>
            <p className="mt-2 text-2xl font-bold text-rose-600">Error</p>
            <p className="text-xs text-rose-500 mt-1">Failed to load metrics</p>
          </div>
        ))}
      </div>
    );
  }

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" aria-label="Dashboard metrics">
      {METRIC_CONFIGS.map((config) => {
        const value = metrics?.[config.key] ?? 0;
        const trend = metrics?.[config.trendKey] as DashboardMetrics[typeof config.trendKey] | undefined;
        
        return (
          <BaseMetricCard
            key={config.key}
            value={config.format ? config.format(value as number) : String(value)}
            label={config.label}
            trendDirection={trend?.direction}
            trendPercentage={trend?.percentage}
            loading={isLoading}
          />
        );
      })}
    </div>
  );
}

export { BaseMetricCard as MetricCard };