import { useQuery } from '@tanstack/react-query';
import { api } from '../../../lib/api';

export const useHomepageData = () => {
  return useQuery({
    queryKey: ['homepage'],
    queryFn: async () => {
      const [eventsRes, categoriesRes] = await Promise.all([
        api.get('/events?limit=10'),
        api.get('/categories'),
      ]);
      return {
        events: eventsRes.data?.data || eventsRes.data || [],
        categories: categoriesRes.data?.data || categoriesRes.data || [],
      };
    },
    staleTime: 5 * 60 * 1000,
  });
};

export const useTrendingEvents = () => {
  return useQuery({
    queryKey: ['trending-events'],
    queryFn: async () => {
      const response = await api.get('/events?sort=trending&limit=3');
      return response.data?.data || response.data || [];
    },
    staleTime: 5 * 60 * 1000,
  });
};

export const useUpcomingEvents = () => {
  return useQuery({
    queryKey: ['upcoming-events'],
    queryFn: async () => {
      const response = await api.get('/events?sort=upcoming&limit=10');
      return response.data?.data || response.data || [];
    },
    staleTime: 5 * 60 * 1000,
  });
};

export const useCategories = () => {
  return useQuery({
    queryKey: ['categories'],
    queryFn: async () => {
      const response = await api.get('/categories');
      return response.data?.data || response.data || [];
    },
    staleTime: 10 * 60 * 1000,
  });
};
