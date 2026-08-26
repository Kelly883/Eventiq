import React, { createContext, useContext } from 'react';

// Initialize cart from localStorage on first load
const INITIAL_CART = JSON.parse(localStorage.getItem('cart') || '[]');

// Create the context with initial state
const CartContext = createContext({
  cart: INITIAL_CART,
  addToCart: (item) => {},
  removeFromCart: (id) => {},
  clearCart: () => {},
});

// Custom hook to get context value
export const useCartContext = () => useContext(CartContext);