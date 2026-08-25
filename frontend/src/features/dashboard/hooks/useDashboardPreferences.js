import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import { api } from '../../../lib/api';

export const useDashboardPreferences = create(
  persist(
    (set) => ({
      expandedEventId: null,
      events: [],
      filters: {
        dateRange: 'all',
        status: 'all',
        search: '',
      },
      isActivityFeedVisible: true,
      
      setExpandedEventId: (eventId) => set({ expandedEventId: eventId }),
      setEvents: (events) => set({ events }),
      setFilters: (newFilters) => set((state) => ({ 
        filters: { ...state.filters, ...newFilters } 
      })),
      toggleActivityFeed: () => set((state) => ({ 
        isActivityFeedVisible: !state.isActivityFeedVisible 
      })),
      setActivityFeedVisible: (visible) => set({ isActivityFeedVisible: visible }),
      
      fetchEvents: async () => {
        try {
          const res = await api.get('/organizer/events');
          set({ events: res.data.data || [] });
        } catch (err) {
          console.error('Failed to fetch events', err);
          set({ events: [] });
        }
      },
    }),
    {
      name: 'dashboard-ui-preferences',
    }
  )
);
