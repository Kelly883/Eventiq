import { QueryClient } from '@tanstack/react-query';

export const queryCacheProfiles = {
  critical: {
    staleTime: 30 * 1000,
    refetchOnWindowFocus: true,
  },
  standard: {
    staleTime: 2 * 60 * 1000,
    refetchOnWindowFocus: false,
  },
  analytics: {
    staleTime: 5 * 60 * 1000,
    refetchOnWindowFocus: false,
  },
} as const;

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: queryCacheProfiles.standard.staleTime,
      gcTime: 5 * 60 * 1000, // 5 minutes
      retry: 3,
      retryDelay: (attemptIndex) => Math.min(1000 * Math.pow(2, attemptIndex), 30000), // Exponential backoff starting at 1s, maxing out at 30s
      refetchOnWindowFocus: queryCacheProfiles.standard.refetchOnWindowFocus,
    },
  },
});

queryClient.setQueryDefaults(['analytics'], queryCacheProfiles.analytics);
queryClient.setQueryDefaults(['payouts'], queryCacheProfiles.analytics);
queryClient.setQueryDefaults(['checkout'], queryCacheProfiles.critical);
queryClient.setQueryDefaults(['payments'], queryCacheProfiles.critical);
