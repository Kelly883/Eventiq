/**
 * Query Key Factories
 * Structured query keys to keep React Query caching organized, predictable, and robust.
 */

export const adminKeys = {
  all: ['admin'] as const,
  access: () => [...adminKeys.all, 'access'] as const,
};

export const eventKeys = {
  all: ['events'] as const,
  lists: () => [...eventKeys.all, 'list'] as const,
  list: (filters: Record<string, any>) => [...eventKeys.lists(), { filters }] as const,
  details: () => [...eventKeys.all, 'detail'] as const,
  detail: (id: string | number) => [...eventKeys.details(), id] as const,
  calendar: (filters?: Record<string, any>) => [...eventKeys.all, 'calendar', filters ? { filters } : {}] as const,
};

export const ticketKeys = {
  all: ['tickets'] as const,
  lists: () => [...ticketKeys.all, 'list'] as const,
  list: (filters: Record<string, any>) => [...ticketKeys.lists(), { filters }] as const,
  details: () => [...ticketKeys.all, 'detail'] as const,
  detail: (id: string | number) => [...ticketKeys.details(), id] as const,
  status: (code: string) => [...ticketKeys.all, 'status', code] as const,
};

export const analyticsKeys = {
  all: ['analytics'] as const,
  summaries: () => [...analyticsKeys.all, 'summary'] as const,
  summary: (filters?: Record<string, any>) => [...analyticsKeys.summaries(), filters ? { filters } : {}] as const,
  velocities: () => [...analyticsKeys.all, 'velocity'] as const,
  velocity: (filters?: Record<string, any>) => [...analyticsKeys.velocities(), filters ? { filters } : {}] as const,
  comparisons: () => [...analyticsKeys.all, 'comparison'] as const,
  comparison: (filters?: Record<string, any>) => [...analyticsKeys.comparisons(), filters ? { filters } : {}] as const,
};

export const checkInKeys = {
  all: ['checkIn'] as const,
  stats: (eventId: string | number) => [...checkInKeys.all, 'stats', eventId] as const,
  lists: (eventId: string | number) => [...checkInKeys.all, 'list', eventId] as const,
  list: (eventId: string | number, filters: Record<string, any>) => [...checkInKeys.lists(eventId), { filters }] as const,
};
