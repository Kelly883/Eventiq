import React, { createContext, useCallback, useContext, useEffect, useState } from 'react';
import { checkoutService } from '../services/checkoutService';

const CART_STORAGE_KEY = 'cart';

const CartContext = createContext({
  cart: [],
  loading: false,
  addToCart: (item) => {},
  removeFromCart: (id) => {},
  clearCart: () => {},
  syncCart: () => {},
  loadCartFromServer: () => {},
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
  const [loading, setLoading] = useState(false);

  // Persess cart to localStorage on every change (offline fallback/cache).
  useEffect(() => {
    localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cart));
  }, [cart]);

  const loadCartFromServer = useCallback(async () => {
    setLoading(true);
    try {
      const serverCart = await checkoutService.getCart();
      if (Array.isArray(serverCart)) {
        setCart(serverCart);
        localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(serverCart));
      }
    } catch {
      // Fall back to localStorage cart if the server is unreachable.
      setCart(readCart());
    } finally {
      setLoading(false);
    }
  }, []);

  const syncCart = useCallback(async () => {
    setLoading(true);
    try {
      await checkoutService.saveCart(cart);
    } catch {
      // Server sync failures are non-fatal; localStorage keeps the cart.
    } finally {
      setLoading(false);
    }
  }, [cart]);

  const addToCart = useCallback(async (item) => {
    setCart((currentCart) => [...currentCart, item]);
    // Fire-and-forget server sync; localStorage already updated above.
    syncCart();
  }, [syncCart]);

  const removeFromCart = useCallback(async (id) => {
    setCart((currentCart) => currentCart.filter((item) => item.id !== id));
    syncCart();
  }, [syncCart]);

  const clearCart = useCallback(async () => {
    setCart([]);
    syncCart();
  }, [syncCart]);

  return (
    <CartContext.Provider value={{ cart, loading, addToCart, removeFromCart, clearCart, syncCart, loadCartFromServer }}>
      {children}
    </CartContext.Provider>
  );
};

export const useCartContext = () => useContext(CartContext);

export { CartContext };
