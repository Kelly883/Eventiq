import { create } from 'zustand';

export const useFraudStore = create((set) => ({
  // Example state
  selectedFraudAlert: null,
  setSelectedFraudAlert: (alert) => set({ selectedFraudAlert: alert }),
}));
