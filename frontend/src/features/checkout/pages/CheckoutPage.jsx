import React, { useEffect, useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { api } from '../../../lib/api';
import { useAuthContext } from '../../../features/auth/context/AuthContext';

const CheckoutPage = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const { user } = useAuthContext();
  const [step, setStep] = useState(1);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  // Check cart status on mount
  useEffect(() => {
    const cart = JSON.parse(localStorage.getItem('cart') || '[]');
    if (cart.length === 0) {
      setError('Your cart is empty');
      // Redirect to cart after a moment
      setTimeout(() => {
        navigate('/cart', { replace: true });
      }, 1500);
    }
  }, []);

  if (error) {
    return (
      <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm mb-6">
        <h2 className="text-xl font-bold text-slate-900 mb-2">Cart Empty</h2>
        <p className="text-slate-500 mb-4">{error}</p>
        <button
          onClick={() => navigate('/cart', { replace: true })}
          className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium shadow-sm hover:bg-indigo-700 transition-colors"
        >
          ← Return to Cart
        </button>
      </div>
    );
  }

  // Handle step changes
  const handleNext = () => setStep((s) => Math.min(s + 1, 4));
  const handlePrev = () => setStep((s) => Math.max(s - 1, 1));

  // Handle checkout submission
  const handleSubmit = async () => {
    setLoading(true);
    setError(null);
    try {
      // In a real implementation, this would process the payment
      // For now, simulate a successful checkout
      setLoading(false);
      setStep(4);
      // Redirect to order confirmation after a delay
      setTimeout(() => {
        navigate(`/order/${Math.floor(Math.random() * 1000) + 1}/confirmation`, {
          replace: true,
        });
      }, 1500);
    } catch (err) {
      setError('Failed to process checkout');
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="mx-auto max-w-3xl">
        {error ? (
          <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm mb-6">
            <h2 className="text-xl font-bold text-slate-900 mb-2">Cart Empty</h2>
            <p className="text-slate-500 mb-4">{error}</p>
            <button
              onClick={() => navigate('/cart', { replace: true })}
              className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium shadow-sm hover:bg-indigo-700 transition-colors"
            >
              ← Return to Cart
            </button>
          </div>
        ) : (
          <div>
            <h1 className="text-2xl font-bold text-slate-900 mb-6">Checkout</h1>

            {step === 1 ? (
              <div className="mb-6">
                <h3 className="text-lg font-bold text-slate-800 mb-2">Cart Summary</h3>
                <p className="text-sm text-slate-500 mb-2">
                  Your cart contains {JSON.parse(localStorage.getItem('cart') || '[]').length} item(s)
                </p>
                <button
                  onClick={() => setStep(2)}
                  className="w-full inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium shadow-sm hover:bg-indigo-700 transition-colors"
                >
                  Proceed to Checkout
                </button>
              </div>
            ) : step === 2 ? (
              <div className="mb-6">
                <h3 className="text-lg font-bold text-slate-800 mb-2">Billing Information</h3>
                <p className="text-sm text-slate-500 mb-4">
                  Please fill in your billing information to complete the purchase.
                </p>
                <div className="mb-4">
                  <label className="block text-sm font-medium text-slate-500 mb-2">
                    Full Name
                  </label>
                  <input
                    type="text"
                    className="w-full bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 p-3"
                    placeholder="Full name" required
                  />
                </div>
                <div className="mb-4">
                  <label className="block text-sm font-medium text-slate-500 mb-2">
                    Email
                  </label>
                  <input
                    type="email"
                    className="w-full bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 p-3"
                    placeholder="name@example.com" required
                  />
                </div>
              </div>
            ) : step === 3 ? (
              <div className="mb-6">
                <h3 className="text-lg font-bold text-slate-800 mb-2">Payment Information</h3>
                <p className="text-sm text-slate-500 mb-4">
                  We'll use a secure payment processor to process your transaction.
                </p>
                <p className="text-sm text-slate-500">
                  No card details are stored on our servers.
                </p>
              </div>
            ) : step === 4 ? (
              <div className="text-center py-12">
                <h1 className="text-2xl font-bold text-slate-900 mb-4">Thank you for your purchase!</h1>
                <p className="text-slate-500 mb-4">
                  Your order has been successfully processed. Your tickets will be
                  delivered to your email address.
                </p>
                <button
                  onClick={() => navigate('/my-tickets', { replace: true })}
                  className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium shadow-sm hover:bg-indigo-700 transition-colors"
                >
                  View My Tickets
                </button>
              </div>
            ) : null}
          </div>
        )}
      </div>
    </div>
  );
};

export default CheckoutPage;