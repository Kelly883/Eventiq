import { create } from 'zustand';

/**
 * Client-side event discovery filters — category, search query, sort order.
 * Kept in Zustand (not React Router search params) so selections survive
 * navigation between EventBrowsePage, EventDetailPage, and back again.
 */
export const useEventStore = create((set) => ({
  searchQuery: '',
  categoryId: null,
  sortBy: 'date_asc', // 'date_asc' | 'date_desc' | 'price_asc' | 'price_desc'

  setSearchQuery: (searchQuery) => set({ searchQuery }),
  setCategoryId: (categoryId) => set({ categoryId }),
  setSortBy: (sortBy) => set({ sortBy }),

  resetFilters: () =>
    set({ searchQuery: '', categoryId: null, sortBy: 'date_asc' }),
}));
