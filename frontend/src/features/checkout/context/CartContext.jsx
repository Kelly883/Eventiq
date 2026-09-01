import React, { createContext, useCallback, useContext, useEffect, useState } from 'react';

const CART_STORAGE_KEY = 'cart';

const CartContext = createContext({
  cart: [],
  addToCart: (item) => {},
  removeFromCart: (id) => {},
  clearCart: () => {},
});

const readCart = () => {
  try {
    const storedCart = JSON.parse(localStorage.getItem(CART_STORAGE_KEY) || '[]');
    return Array.isArray(storedCart) ? storedCart : [];
  } catch {
    return [];
  }
};

export const CartProvider = ({ children }) => {
  const [cart, setCart] = useState(readCart);

  useEffect(() => {
    localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cart));
  }, [cart]);

  const addToCart = useCallback((item) => {
    setCart((currentCart) => [...currentCart, item]);
  }, []);

  const removeFromCart = useCallback((id) => {
    setCart((currentCart) => currentCart.filter((item) => item.id !== id));
  }, []);

  const clearCart = useCallback(() => {
    setCart([]);
  }, []);

  return (
    <CartContext.Provider value={{ cart, addToCart, removeFromCart, clearCart }}>
      {children}
    </CartContext.Provider>
  );
};

export const useCartContext = () => useContext(CartContext);

export { CartContext };