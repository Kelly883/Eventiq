import type { MetricDateRange } from '../hooks/useDashboardMetrics';

/**
 * The dashboard UI keeps its own date-range vocabulary (`all`, `today`,
 * `this-week`, `this-month`) in the persisted preferences store, while the
 * metrics endpoint speaks `last_7_days` / `last_30_days` / `all_time`.
 * Translating in one place keeps the store human-readable and the API happy.
 */
export const toMetricDateRange = (dateRange?: string | null): MetricDateRange => {
  switch (dateRange) {
    case 'today':
    case 'this-week':
    case '7d':
      return 'last_7_days';
    case 'this-month':
    case '30d':
      return 'last_30_days';
    case '90d':
      return 'last_90_days';
    case 'all':
    case 'all_time':
    case '':
    case null:
    case undefined:
      return 'all_time';
    default:
      return 'last_30_days';
  }
};

/** Event status vocabulary used by the filter store -> API status filter. */
export const toEventStatusFilter = (status?: string | null): string | undefined => {
  switch (status) {
    case 'active':
      return 'published';
    case 'cancelled':
      return 'cancelled';
    case 'draft':
      return 'draft';
    // 'completed' maps to no filter: the list endpoint only accepts
    // published/draft/cancelled/flagged, and "everything else" is what the
    // UI means by completed.
    case 'completed':
    case 'all':
    case '':
    case null:
    case undefined:
      return undefined;
    default:
      return status;
  }
};
