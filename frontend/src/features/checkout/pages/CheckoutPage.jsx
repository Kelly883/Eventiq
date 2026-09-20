import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { api } from '../../../lib/api';
import { useAuthContext } from '../../../features/auth/context/AuthContext';
import { useCartContext } from '../context/CartContext';
import '../../../styles/shared-pages.css';

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

  const handleSubmit = async () => {
    setLoading(true);
    setError(null);
    try {
      if (cart.length === 0) {
        setError('Your cart is empty');
        setLoading(false);
        return;
      }
      await api.post('/cart/verify', { items: cart });
      const res = await api.post('/checkout/create-payment-intent', {
        event_id: cart[0]?.event_id,
        gateway: 'paystack',
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

  if (error && cart.length === 0) {
    return (
      <div className="spa-page">
        <div className="spa-container" style={{ maxWidth: 600 }}>
          <div className="spa-card spa-card--padded spa-empty">
            <h2 className="spa-empty__title">Cart Empty</h2>
            <p className="spa-empty__text">{error}</p>
            <button onClick={() => navigate('/cart', { replace: true })} className="spa-btn spa-btn--primary">
              ← Return to Cart
            </button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="spa-page">
      <div className="spa-container" style={{ maxWidth: 640 }}>
        <div className="spa-page__header">
          <h1 className="spa-page__title">Checkout</h1>
          <p className="spa-page__subtitle">Step {step} of 3</p>
        </div>

        <div className="spa-card spa-card--padded">
          {step === 1 && (
            <>
              <h3 style={{ margin: '0 0 8px', fontSize: '1.125rem', fontWeight: 700 }}>Cart Summary</h3>
              <p style={{ fontSize: 14, color: '#666', marginBottom: 20 }}>
                Your cart contains {cart.length} item(s)
              </p>
              <button onClick={() => setStep(2)} className="spa-btn spa-btn--primary">
                Proceed to Checkout
              </button>
            </>
          )}

          {step === 2 && (
            <>
              <h3 style={{ margin: '0 0 8px', fontSize: '1.125rem', fontWeight: 700 }}>Billing Information</h3>
              <p style={{ fontSize: 14, color: '#666', marginBottom: 20 }}>
                Please fill in your billing information to complete the purchase.
              </p>
              <div className="spa-field">
                <label htmlFor="checkout-full-name">Full Name</label>
                <input
                  id="checkout-full-name"
                  name="fullName"
                  type="text"
                  value={billingDetails.fullName}
                  onChange={(e) => setBillingDetails((d) => ({ ...d, fullName: e.target.value }))}
                  className="spa-input"
                  placeholder="Full name"
                  required
                />
              </div>
              <div className="spa-field">
                <label htmlFor="checkout-email">Email</label>
                <input
                  id="checkout-email"
                  name="email"
                  type="email"
                  value={billingDetails.email}
                  onChange={(e) => setBillingDetails((d) => ({ ...d, email: e.target.value }))}
                  className="spa-input"
                  placeholder="name@example.com"
                  required
                />
              </div>
              <div style={{ display: 'flex', gap: 12, marginTop: 8 }}>
                <button type="button" onClick={() => setStep(1)} className="spa-btn spa-btn--secondary">
                  ← Back to Cart
                </button>
                <button
                  type="button"
                  onClick={() => setStep(3)}
                  disabled={!billingDetails.fullName.trim() || !billingDetails.email.trim()}
                  className="spa-btn spa-btn--primary"
                >
                  Continue to Payment
                </button>
              </div>
            </>
          )}

          {step === 3 && (
            <>
              <h3 style={{ margin: '0 0 8px', fontSize: '1.125rem', fontWeight: 700 }}>Payment Information</h3>
              <p style={{ fontSize: 14, color: '#666', marginBottom: 8 }}>
                We'll use a secure payment processor to process your transaction.
              </p>
              <p style={{ fontSize: 14, color: '#666', marginBottom: 24 }}>
                No card details are stored on our servers.
              </p>
              <div style={{ display: 'flex', gap: 12 }}>
                <button type="button" onClick={() => setStep(2)} disabled={loading} className="spa-btn spa-btn--secondary">
                  ← Back
                </button>
                <button type="button" onClick={handleSubmit} disabled={loading} className="spa-btn spa-btn--primary">
                  {loading ? 'Processing payment...' : 'Pay and Complete Order'}
                </button>
              </div>
            </>
          )}
        </div>
      </div>
    </div>
  );
};

export default CheckoutPage;
