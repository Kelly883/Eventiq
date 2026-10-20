import { useQuery } from '@tanstack/react-query';
import { useRef, useEffect } from 'react';
import { api } from '../../../lib/api';

export interface DashboardMetrics {
  totalRevenue: number;
  totalEvents: number;
  totalAttendees: number;
  totalTicketsSold: number;
  revenueTrend: { direction: 'up' | 'down' | 'neutral'; percentage: string };
  eventsTrend: { direction: 'up' | 'down' | 'neutral'; percentage: string };
  attendeesTrend: { direction: 'up' | 'down' | 'neutral'; percentage: string };
  ticketsTrend: { direction: 'up' | 'down' | 'neutral'; percentage: string };
}

export function useDashboardMetrics() {
  return useQuery({
    queryKey: ['dashboard', 'metrics'],
    queryFn: async (): Promise<DashboardMetrics> => {
      const res = await api.get('/dashboard/metrics');
      return res.data;
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
  quantity: number;
  price: number;
}

export interface SalesActivityResponse {
  activities: SalesActivityItem[];
  lastRefresh: string;
}

export function useSalesActivity(filters: {
  event?: string;
  tier?: string;
  dateFrom?: string;
  dateTo?: string;
}) {
  return useQuery({
    queryKey: ['sales', 'activity', filters],
    queryFn: async (): Promise<SalesActivityItem[]> => {
      const res = await api.get('/activity/feed', { params: filters });
      return res.data?.activities || res.data || [];
    },
    staleTime: 1000 * 30,
    // SSE handles real-time updates; no polling needed
    refetchInterval: false,
    retry: 1,
  });
}

export function useSalesActivitySSE(onMessage: (activities: SalesActivityItem[]) => void) {
  const eventSourceRef = useRef<EventSource | null>(null);
  const reconnectTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const onMessageRef = useRef(onMessage);
  
  // Keep callback ref current without triggering effect re-run
  useEffect(() => {
    onMessageRef.current = onMessage;
  }, [onMessage]);

  useEffect(() => {
    if (typeof window === 'undefined') return;

    const connect = () => {
      const eventSource = new EventSource('/api/activity/stream');
      eventSourceRef.current = eventSource;

      eventSource.onmessage = (event) => {
        try {
          const data = JSON.parse(event.data);
          if (data.activities) {
            onMessageRef.current(data.activities);
          }
        } catch (err) {
          console.error('Failed to parse SSE message:', err);
        }
      };

      eventSource.onerror = () => {
        console.warn('SSE connection lost, reconnecting in 5s...');
        eventSource.close();
        
        if (reconnectTimeoutRef.current) {
          clearTimeout(reconnectTimeoutRef.current);
        }
        
        reconnectTimeoutRef.current = setTimeout(() => {
          connect();
        }, 5000);
      };
    };

    connect();

    return () => {
      if (eventSourceRef.current) {
        eventSourceRef.current.close();
      }
      if (reconnectTimeoutRef.current) {
        clearTimeout(reconnectTimeoutRef.current);
      }
    };
  }, []);

  return eventSourceRef;
}