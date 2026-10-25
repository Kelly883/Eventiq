import { useQuery } from '@tanstack/react-query';
import { useRef, useEffect } from 'react';
import { api } from '../../../lib/api';

export type TrendDirection = 'up' | 'down' | 'neutral';

export interface Trend {
  direction: TrendDirection;
  percentage: string;
}

export interface DashboardMetrics {
  totalRevenue: number;
  totalEvents: number;
  totalTicketsSold: number;
  totalPageViews: number;
  revenueTrend: Trend;
  eventsTrend: Trend;
  ticketsTrend: Trend;
  pageViewsTrend: Trend;
}

/** UI date ranges -> the values the backend `overview` endpoint understands. */
export const METRIC_DATE_RANGES = [
  'last_7_days',
  'last_30_days',
  'last_90_days',
  'all_time',
] as const;

export type MetricDateRange = (typeof METRIC_DATE_RANGES)[number];

const NEUTRAL_TREND: Trend = { direction: 'neutral', percentage: '0%' };

/**
 * The backend reports a trend as `{ direction: 'up' | 'down' | 'flat',
 * percentageChange: number }`. The card expects `neutral` and a pre-formatted
 * percentage string, so translate once here instead of in every consumer.
 */
export const normalizeTrend = (raw?: {
  direction?: string;
  percentageChange?: number;
}): Trend => {
  const direction: TrendDirection =
    raw?.direction === 'up' ? 'up' : raw?.direction === 'down' ? 'down' : 'neutral';

  const pct = typeof raw?.percentageChange === 'number' && Number.isFinite(raw.percentageChange)
    ? raw.percentageChange
    : 0;

  const rounded = Math.round(pct * 10) / 10;
  const magnitude = Number.isInteger(rounded) ? String(rounded) : rounded.toFixed(1);
  const sign = rounded > 0 ? '+' : '';

  return { direction, percentage: `${sign}${magnitude}%` };
};

interface RawMetricsPayload {
  metrics?: {
    totalEvents?: number;
    eventsPublished?: number;
    totalTicketsSold?: number;
    totalRevenue?: number;
    totalPageViews?: number;
  };
  trends?: {
    ticketsSold?: { direction?: string; percentageChange?: number };
    revenue?: { direction?: string; percentageChange?: number };
    pageViews?: { direction?: string; percentageChange?: number };
  };
}

/** Maps the API payload onto the shape the metric cards render. */
export const normalizeMetrics = (payload: RawMetricsPayload | undefined): DashboardMetrics => {
  const metrics = payload?.metrics ?? {};
  const trends = payload?.trends ?? {};

  return {
    totalRevenue: Number(metrics.totalRevenue ?? 0),
    totalEvents: Number(metrics.totalEvents ?? 0),
    totalTicketsSold: Number(metrics.totalTicketsSold ?? 0),
    totalPageViews: Number(metrics.totalPageViews ?? 0),
    revenueTrend: normalizeTrend(trends.revenue),
    // The backend does not track an event-count trend yet.
    eventsTrend: NEUTRAL_TREND,
    ticketsTrend: normalizeTrend(trends.ticketsSold),
    pageViewsTrend: normalizeTrend(trends.pageViews),
  };
};

export function useDashboardMetrics(dateRange: MetricDateRange = 'last_30_days', category?: string) {
  return useQuery({
    queryKey: ['dashboard', 'metrics', dateRange, category ?? 'all'],
    queryFn: async (): Promise<DashboardMetrics> => {
      const res = await api.get('/organizer/dashboard/metrics', {
        params: {
          dateRange,
          category: category && category !== 'all' ? category : undefined,
        },
      });
      return normalizeMetrics(res.data);
    },
    staleTime: 1000 * 60 * 5,
    retry: 1,
  });
}

export interface SalesActivityItem {
  id: string;
  timestamp: string;
  eventId: string;
  eventName: string;
  eventInitials: string;
  tier: string;
  tierId?: string;
  quantity: number;
  price: number;
}

export interface SalesActivityResponse {
  activities: SalesActivityItem[];
  lastRefresh: string;
}

/** The feed must feel live even when SSE is unavailable, so poll as a floor. */
export const ACTIVITY_POLL_INTERVAL_MS = 30_000;

/** Filters as the UI holds them; translated to API params below. */
export interface ActivityFilterInput {
  event?: string;
  tier?: string;
  dateFrom?: string;
  dateTo?: string;
}

/**
 * The UI filters by tier id and event id, while the API expects the same
 * values under different names. A date-only `dateTo` is widened to the end
 * of that day so sales later in the evening are not dropped.
 */
export const toActivityQueryParams = (filters: ActivityFilterInput) => {
  const params: Record<string, string | number> = { limit: 50 };

  if (filters.event) params.eventId = filters.event;
  if (filters.tier) params.tierId = filters.tier;

  if (filters.dateFrom) {
    const from = new Date(filters.dateFrom);
    if (!Number.isNaN(from.getTime())) params.startDate = from.toISOString();
  }

  if (filters.dateTo) {
    const to = new Date(filters.dateTo);
    if (!Number.isNaN(to.getTime())) {
      to.setHours(23, 59, 59, 999);
      params.endDate = to.toISOString();
    }
  }

  return params;
};

/** Defensive mapping of an API activity row onto the shape the feed renders. */
export const normalizeActivity = (raw: any): SalesActivityItem => ({
  id: String(raw?.id ?? ''),
  timestamp: raw?.timestamp ?? '',
  eventId: String(raw?.eventId ?? raw?.event_id ?? ''),
  eventName: raw?.eventName ?? raw?.event_name ?? '',
  eventInitials: raw?.eventInitials ?? raw?.event_initials ?? 'EV',
  tier: raw?.tier ?? raw?.tierName ?? '',
  tierId: raw?.tierId ?? raw?.tier_id ?? undefined,
  quantity: Number(raw?.quantity ?? 0),
  price: Number(raw?.price ?? raw?.unitPrice ?? 0),
});

export function useSalesActivity(filters: ActivityFilterInput) {
  const streamUrl = typeof import.meta !== 'undefined'
    ? (import.meta as unknown as { env?: Record<string, string> }).env?.VITE_ACTIVITY_STREAM_URL
    : undefined;

  return useQuery({
    queryKey: ['sales', 'activity', filters],
    queryFn: async (): Promise<SalesActivityItem[]> => {
      const res = await api.get('/organizer/dashboard/activity-feed', {
        params: toActivityQueryParams(filters),
      });
      const rows = res.data?.activities ?? res.data ?? [];
      return Array.isArray(rows) ? rows.map(normalizeActivity) : [];
    },
    staleTime: 1000 * 15,
    refetchInterval: streamUrl ? false : ACTIVITY_POLL_INTERVAL_MS,
    refetchIntervalInBackground: false,
    refetchOnWindowFocus: true,
    retry: 1,
  });
}

const SSE_MAX_CONSECUTIVE_FAILURES = 5;
const SSE_MAX_BACKOFF_MS = 30_000;

/**
 * Optional real-time upgrade over the 30s poll.
 *
 * The backend does not ship an SSE endpoint yet, so the stream is opt-in via
 * `VITE_ACTIVITY_STREAM_URL`. Without it we never open a connection (and
 * never log a stream error); polling keeps the feed current. When a stream
 * IS configured we still fall back to polling if it keeps failing.
 */
export function useSalesActivitySSE(onMessage: (activities: SalesActivityItem[]) => void) {
  const eventSourceRef = useRef<EventSource | null>(null);
  const reconnectTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const onMessageRef = useRef(onMessage);

  // Keep callback ref current without triggering effect re-run
  useEffect(() => {
    onMessageRef.current = onMessage;
  }, [onMessage]);

  useEffect(() => {
    if (typeof window === 'undefined' || typeof EventSource === 'undefined') return;

    const streamUrl = (import.meta as unknown as { env?: Record<string, string> }).env
      ?.VITE_ACTIVITY_STREAM_URL;
    if (!streamUrl) return;

    let disposed = false;
    let consecutiveFailures = 0;

    const scheduleReconnect = () => {
      if (disposed) return;
      const delay = Math.min(SSE_MAX_BACKOFF_MS, 1000 * 2 ** consecutiveFailures);
      reconnectTimeoutRef.current = setTimeout(connect, delay);
    };

    const connect = () => {
      if (disposed) return;
      const eventSource = new EventSource(streamUrl);
      eventSourceRef.current = eventSource;

      eventSource.onopen = () => {
        consecutiveFailures = 0;
      };

      eventSource.onmessage = (event) => {
        try {
          const data = JSON.parse(event.data);
          if (data.activities) {
            onMessageRef.current((data.activities as any[]).map(normalizeActivity));
          }
        } catch {
          console.error('[activity-stream] ignoring malformed SSE payload');
        }
      };

      eventSource.onerror = () => {
        eventSource.close();
        if (disposed) return;

        consecutiveFailures += 1;
        if (consecutiveFailures > SSE_MAX_CONSECUTIVE_FAILURES) {
          console.warn('[activity-stream] stream unavailable; relying on 30s polling');
          return;
        }
        scheduleReconnect();
      };
    };

    connect();

    return () => {
      disposed = true;
      eventSourceRef.current?.close();
      if (reconnectTimeoutRef.current) {
        clearTimeout(reconnectTimeoutRef.current);
      }
    };
  }, []);

  return eventSourceRef;
}
