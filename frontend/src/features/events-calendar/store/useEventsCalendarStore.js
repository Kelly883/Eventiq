import { create } from 'zustand';

export const useEventsCalendarStore = create((set) => ({
  // Example state
  selectedDate: null,
  setSelectedDate: (date) => set({ selectedDate: date }),
  filters: {},
  setFilters: (filters) => set({ filters }),
}));
