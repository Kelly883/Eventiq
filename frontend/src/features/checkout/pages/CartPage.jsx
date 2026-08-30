import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useCartContext } from '../context/CartContext';
import { api } from '../../../lib/api';

const CartPage = () => {
  const navigate = useNavigate();
  const { cart, addToCart, removeFromCart, clearCart } = useCartContext();
  const [verifying, setVerifying] = useState(false);
  const itemCount = cart.length;

  // Verify cart on mount - lightweight check that items are still valid
  useEffect(() => {
    setVerifying(true);
    const cartItems = cart.map(item => item.id || item.ticketId || item.ticket_id || item.id);
    if (cartItems.length > 0) {
      api.post('/cart/verify', { items: cart })
        .then(() => {
          // Cart verification successful
        })
        .catch(() => {
          // Verification failed - continue anyway, cart items will be re-verified at checkout
        });
    }
    setVerifying(false);
  }, [cart]);

  return (
    <div className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="mx-auto max-w-2xl">
        <h1 className="text-2xl font-bold text-slate-900 mb-6">Your Cart</h1>

        {itemCount === 0 ? (
          <div className="bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-500">
            <h2 className="text-lg font-medium text-slate-500 mb-4">Cart is empty</h2>
            <p className="text-slate-400">
              Your cart doesn't have any items. <Link to="/events" className="font-medium text-indigo-600 hover:text-indigo-800">Browse events</Link> to add tickets to your cart.
            </p>
            {/* Disabled proceed-to-checkout message when cart is empty */}
            <p className="mt-4 text-sm text-slate-400">Your cart is empty. Add tickets from events to get started.</p>
          </div>
        ) : (
          <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
            <h2 className="text-lg font-bold text-slate-800 mb-4">Cart Items</h2>
            <p className="text-sm text-slate-500 mb-4">
              <span className="font-medium">Items: {itemCount}</span>
              <span className="ml-2 text-slate-400">
                {itemCount === 1 ? 'item' : 'items'}
              </span>
            </p>
            <ul className="space-y-3 text-sm text-slate-500">
              {cart.map((item, index) => (
                <li key={index} className="flex items-center gap-3">
                  <span className="font-medium">{item.name || item.title || 'Item'}</span>
                  <span className="text-slate-400">{item.quantity || 1}x</span>
                  <button
                    onClick={() => removeFromCart(item.id)}
                    className="ml-3 text-xs text-indigo-600 hover:text-indigo-800 underline"
                  >
                    Remove
                  </button>
                </li>
              ))}
            </ul>
            <div className="mt-4 pt-4 border-t border-slate-200">
              <p className="text-sm text-slate-500">Total: {itemCount} item(s)</p>
              <button
                onClick={() => navigate('/checkout')}
                className="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium shadow-sm hover:bg-indigo-700 transition-colors"
                disabled={verifying}
              >
                <span className="font-medium">Proceed to Checkout</span>
                <span className="ml-2 arrow">→</span>
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};
export default CartPage;