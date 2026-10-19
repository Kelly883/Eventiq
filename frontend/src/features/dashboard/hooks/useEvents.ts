import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { api } from '../../../lib/api';

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
  tiers?: Array<{
    tier: string;
    sold: number;
    capacity: number;
    price: number;
  }>;
  pricing_timeline?: Array<{
    label: string;
    price: number;
    date: string;
  }>;
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

export function useEvents() {
  return useQuery({
    queryKey: EVENTS_QUERY_KEY,
    queryFn: async (): Promise<EventData[]> => {
      const res = await api.get('/organizer/events');
      return res.data.data || res.data || [];
    },
    staleTime: 1000 * 60 * 2,
  });
}

export function useEventDetail(eventId: string | number | null, enabled = true) {
  return useQuery({
    queryKey: EVENT_DETAIL_QUERY_KEY(eventId!),
    queryFn: async (): Promise<EventData> => {
      const res = await api.get(`/events/${eventId}`);
      return res.data;
    },
    enabled: !!eventId && enabled,
    staleTime: 1000 * 60 * 5,
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
    onSuccess: (_, { id }) => {
      queryClient.invalidateQueries({ queryKey: EVENTS_QUERY_KEY });
      queryClient.invalidateQueries({ queryKey: EVENT_DETAIL_QUERY_KEY(id) });
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
        const res = await api.get(`/events/${eventId}`);
        return res.data;
      },
      staleTime: 1000 * 60 * 5,
    });
  };
}