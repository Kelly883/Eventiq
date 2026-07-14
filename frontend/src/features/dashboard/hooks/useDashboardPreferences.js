import { create } from 'zustand';
import { persist } from 'zustand/middleware';

export const useDashboardPreferences = create(
  persist(
    (set) => ({
      expandedEventId: null,
      filters: {
        dateRange: 'all',
        status: 'all',
        search: '',
      },
      isActivityFeedVisible: true,
      
      setExpandedEventId: (eventId) => set({ expandedEventId: eventId }),
      setFilters: (newFilters) => set((state) => ({ 
        filters: { ...state.filters, ...newFilters } 
      })),
      toggleActivityFeed: () => set((state) => ({ 
        isActivityFeedVisible: !state.isActivityFeedVisible 
      })),
      setActivityFeedVisible: (visible) => set({ isActivityFeedVisible: visible }),
    }),
    {
      name: 'dashboard-ui-preferences',
    }
  )
);
