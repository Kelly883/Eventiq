import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '../../../lib/api';

export interface EventTier {
  tier: string;
  sold: number;
  capacity: number;
  price: number;
}

export interface EventPricingPoint {
  label: string;
  price: number;
  date: string;
}

export interface EventData {
  id: string | number;
  title: string;
  desc?: string;
  bannerUrl?: string;
  status?: string;
  startDate?: string;
  endDate?: string;
  start_date?: string;
  end_date?: string;
  venueName?: string;
  venue_name?: string;
  revenue?: number;
  attendeeCount?: number;
  ticketsSold?: number;
  ticketsTotal?: number;
  progressPercent?: number;
  organizerName?: string;
  category?: string;
  tiers?: EventTier[];
  pricing_timeline?: EventPricingPoint[];
}

export interface EventsResponse {
  data: EventData[];
  meta?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface EventDetailResponse {
  data: EventData;
}

const EVENTS_QUERY_KEY = ['organizer', 'events'];
const EVENT_DETAIL_QUERY_KEY = (eventId: string | number) => ['organizer', 'events', eventId];

/** Backend status -> the badge vocabulary the dashboard card understands. */
const STATUS_MAP: Record<string, string> = {
  published: 'active',
  live: 'active',
  past: 'completed',
  archived: 'completed',
  completed: 'completed',
  cancelled: 'cancelled',
  draft: 'draft',
};

export const normalizeEventStatus = (status?: string | null): string => {
  const key = String(status ?? '').toLowerCase();
  return STATUS_MAP[key] ?? 'active';
};

const toNumber = (value: unknown): number => {
  const num = Number(value);
  return Number.isFinite(num) ? num : 0;
};

/** Maps one API event row onto the shape the dashboard card renders. */
export const normalizeEvent = (raw: any): EventData => {
  const ticketsSold = toNumber(raw?.ticketsSold ?? raw?.tickets_sold);
  const capacity = toNumber(raw?.capacity ?? raw?.ticketsTotal);
  const progress =
    raw?.progressPercent != null
      ? toNumber(raw.progressPercent)
      : capacity > 0
        ? Math.round((ticketsSold / capacity) * 100)
        : 0;

  return {
    id: raw?.id,
    title: raw?.title ?? 'Untitled Event',
    desc: raw?.description ?? raw?.desc ?? undefined,
    bannerUrl: raw?.bannerUrl ?? raw?.banner_image_url ?? undefined,
    status: normalizeEventStatus(raw?.status),
    startDate: raw?.startDatetime ?? raw?.start_date ?? raw?.start_datetime ?? undefined,
    endDate: raw?.endDatetime ?? raw?.end_date ?? raw?.end_datetime ?? undefined,
    venueName: raw?.venueName ?? raw?.venue_name ?? undefined,
    revenue: toNumber(raw?.revenue),
    attendeeCount: toNumber(raw?.attendeeCount ?? raw?.attendee_count ?? ticketsSold),
    ticketsSold,
    ticketsTotal: capacity,
    progressPercent: Math.min(100, Math.max(0, progress)),
    organizerName: raw?.organizerName ?? raw?.organizer?.displayName ?? undefined,
    category: raw?.category ?? undefined,
  };
};

export interface EventListFilters {
  status?: string;
  category?: string;
  dateRange?: string;
  search?: string;
}

/**
 * The dashboard list endpoint speaks the same vocabulary as the metric
 * endpoint, so the card always gets revenue/tickets/venue/banner in one shot.
 */
export function useEvents(filters: EventListFilters = {}) {
  return useQuery({
    queryKey: [...EVENTS_QUERY_KEY, filters],
    queryFn: async (): Promise<EventData[]> => {
      const res = await api.get('/organizer/dashboard/events', {
        params: {
          status: filters.status && filters.status !== 'all' ? filters.status : undefined,
          category: filters.category && filters.category !== 'all' ? filters.category : undefined,
          perPage: 50,
        },
      });
      const rows = res.data?.events ?? res.data?.data ?? res.data ?? [];
      return Array.isArray(rows) ? rows.map(normalizeEvent) : [];
    },
    staleTime: 1000 * 60 * 2,
  });
}

/** Maps the API event-detail payload onto the shape the widget renders. */
export const normalizeEventDetail = (raw: any): EventData => {
  const event = raw?.event ?? raw?.data ?? raw;
  const metrics = event?.metrics ?? {};

  const tiers: EventTier[] = (event?.tiers ?? []).map((tier: any) => ({
    tier: tier?.name ?? tier?.tier ?? 'General',
    sold: toNumber(tier?.soldCount ?? tier?.sold),
    capacity: toNumber(tier?.quantity ?? tier?.capacity),
    price: toNumber(tier?.price),
  }));

  const pricingTimeline: EventPricingPoint[] = (event?.pricingTimeline ?? event?.pricing_timeline ?? []).map(
    (point: any) => ({
      label: point?.label ?? point?.windowName ?? 'Price change',
      price: toNumber(point?.price),
      date: point?.date ?? point?.startDate ?? '',
    }),
  );

  const ticketsSold = toNumber(metrics.totalTicketsSold ?? event?.ticketsSold);

  return {
    id: event?.id,
    title: event?.title ?? 'Untitled Event',
    desc: event?.description ?? event?.desc ?? undefined,
    bannerUrl: event?.bannerUrl ?? event?.banner_image_url ?? undefined,
    status: normalizeEventStatus(event?.status),
    startDate: event?.startDatetime ?? event?.startDate ?? event?.start_date ?? undefined,
    endDate: event?.endDatetime ?? event?.endDate ?? event?.end_date ?? undefined,
    venueName: event?.venueName ?? event?.venue_name ?? undefined,
    revenue: toNumber(metrics.totalRevenue ?? event?.revenue),
    attendeeCount: toNumber(event?.attendeeCount ?? ticketsSold),
    ticketsSold,
    ticketsTotal: toNumber(event?.capacity),
    progressPercent:
      event?.capacity > 0 ? Math.round((ticketsSold / Number(event.capacity)) * 100) : 0,
    organizerName: event?.organizerName ?? event?.organizer?.displayName ?? undefined,
    tiers,
    pricing_timeline: pricingTimeline,
  };
};

export function useEventDetail(eventId: string | number | null, enabled = true) {
  return useQuery({
    queryKey: EVENT_DETAIL_QUERY_KEY(eventId!),
    queryFn: async (): Promise<EventData> => {
      const res = await api.get(`/organizer/dashboard/events/${eventId}`);
      return normalizeEventDetail(res.data);
    },
    enabled: !!eventId && enabled,
    staleTime: 1000 * 60 * 5,
    retry: 1,
  });
}

export function useCreateEvent() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (data: Partial<EventData>) => {
      const res = await api.post('/organizer/events', data);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: EVENTS_QUERY_KEY });
    },
  });
}

export function useUpdateEvent() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ id, data }: { id: string | number; data: Partial<EventData> }) => {
      const res = await api.put(`/organizer/events/${id}`, data);
      return res.data;
    },
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: EVENTS_QUERY_KEY });
      queryClient.invalidateQueries({ queryKey: EVENT_DETAIL_QUERY_KEY(variables.id) });
    },
  });
}

export function useDeleteEvent() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (id: string | number) => {
      const res = await api.delete(`/organizer/events/${id}`);
      return res.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: EVENTS_QUERY_KEY });
    },
  });
}

export function usePrefetchEvent(eventId: string | number) {
  const queryClient = useQueryClient();

  return () => {
    queryClient.prefetchQuery({
      queryKey: EVENT_DETAIL_QUERY_KEY(eventId),
      queryFn: async () => {
        const res = await api.get(`/organizer/dashboard/events/${eventId}`);
        return normalizeEventDetail(res.data);
      },
      staleTime: 1000 * 60 * 5,
    });
  };
}
