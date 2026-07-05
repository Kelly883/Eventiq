import { create } from 'zustand';

export const useCheckoutStore = create((set) => ({
  // Example state
  cart: [],
  addToCart: (item) => set((state) => ({ cart: [...state.cart, item] })),
  removeFromCart: (id) => set((state) => ({ cart: state.cart.filter(i => i.id !== id) })),
}));
