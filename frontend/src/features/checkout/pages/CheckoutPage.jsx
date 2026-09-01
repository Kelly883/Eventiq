import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { api } from '../../../lib/api';
import { useAuthContext } from '../../../features/auth/context/AuthContext';
import { useCartContext } from '../context/CartContext';

const CheckoutPage = () => {
  const navigate = useNavigate();
  const { user } = useAuthContext();
  const { cart } = useCartContext();
  const [step, setStep] = useState(1);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [billingDetails, setBillingDetails] = useState({
    fullName: user?.name || '',
    email: user?.email || '',
  });

  // Check cart status on mount
  useEffect(() => {
    if (cart.length === 0) {
      setError('Your cart is empty');
      const redirectTimer = setTimeout(() => {
        navigate('/cart', { replace: true });
      }, 1500);
      return () => clearTimeout(redirectTimer);
    }
    setError(null);
    return undefined;
  }, [cart.length, navigate]);

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

  // Handle checkout submission
  const handleSubmit = async () => {
    setLoading(true);
    setError(null);
    try {
      // Verify cart first
      if (cart.length === 0) {
        setError('Your cart is empty');
        setLoading(false);
        return;
      }
      // Verify cart prices/availability
      await api.post('/cart/verify', { items: cart });
      // Create payment intent
      const res = await api.post('/checkout/create-payment-intent', {
        event_id: cart[0]?.event_id,
        gateway: 'paystack', // default gateway
        items: cart,
      });
      setLoading(false);
      const orderId = res?.data?.order_id;
      if (!orderId) {
        throw new Error('Checkout did not return an order ID');
      }

      const gatewayUrl = res?.data?.gateway_data?.authorization_url
        || res?.data?.gateway_data?.link;
      if (!gatewayUrl) {
        throw new Error('Payment gateway did not return a checkout URL');
      }

      window.location.assign(gatewayUrl);
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
                  Your cart contains {cart.length} item(s)
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
                  <label htmlFor="checkout-full-name" className="block text-sm font-medium text-slate-500 mb-2">
                    Full Name
                  </label>
                  <input
                    id="checkout-full-name"
                    name="fullName"
                    type="text"
                    value={billingDetails.fullName}
                    onChange={(event) => setBillingDetails((details) => ({
                      ...details,
                      fullName: event.target.value,
                    }))}
                    className="w-full bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 p-3"
                    placeholder="Full name"
                    required
                  />
                </div>
                <div className="mb-4">
                  <label htmlFor="checkout-email" className="block text-sm font-medium text-slate-500 mb-2">
                    Email
                  </label>
                  <input
                    id="checkout-email"
                    name="email"
                    type="email"
                    value={billingDetails.email}
                    onChange={(event) => setBillingDetails((details) => ({
                      ...details,
                      email: event.target.value,
                    }))}
                    className="w-full bg-slate-50 border border-slate-200 text-slate-700 text-sm rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 p-3"
                    placeholder="name@example.com"
                    required
                  />
                </div>
                <div className="flex gap-3">
                  <button
                    type="button"
                    onClick={() => setStep(1)}
                    className="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-200 bg-white text-sm font-medium"
                  >
                    ← Back to Cart
                  </button>
                  <button
                    type="button"
                    onClick={() => setStep(3)}
                    disabled={!billingDetails.fullName.trim() || !billingDetails.email.trim()}
                    className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium shadow-sm hover:bg-indigo-700 transition-colors disabled:opacity-50"
                  >
                    Continue to Payment
                  </button>
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
                <div className="mt-6 flex gap-3">
                  <button
                    type="button"
                    onClick={() => setStep(2)}
                    disabled={loading}
                    className="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-slate-200 bg-white text-sm font-medium disabled:opacity-50"
                  >
                    ← Back
                  </button>
                  <button
                    type="button"
                    onClick={handleSubmit}
                    disabled={loading}
                    className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium shadow-sm hover:bg-indigo-700 transition-colors disabled:opacity-50"
                  >
                    {loading ? 'Processing payment...' : 'Pay and Complete Order'}
                  </button>
                </div>
              </div>
            ) : null}
          </div>
        )}
      </div>
    </div>
  );
};

export default CheckoutPage;