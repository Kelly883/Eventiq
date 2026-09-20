import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useCartContext } from '../context/CartContext';
import { api } from '../../../lib/api';
import '../../../styles/shared-pages.css';

const CartPage = () => {
  const navigate = useNavigate();
  const { cart, removeFromCart } = useCartContext();
  const [verifying, setVerifying] = useState(false);
  const [verifyError, setVerifyError] = useState(null);
  const itemCount = cart.length;

  useEffect(() => {
    let cancelled = false;
    setVerifying(true);
    setVerifyError(null);
    if (cart.length > 0) {
      api.post('/cart/verify', { items: cart })
        .then(() => { if (!cancelled) setVerifyError(null); })
        .catch(() => { if (!cancelled) setVerifyError('Some cart items may be unavailable. Please review before checkout.'); })
        .finally(() => { if (!cancelled) setVerifying(false); });
    } else {
      setVerifying(false);
    }
    return () => { cancelled = true; };
  }, [cart]);

  const handleRetry = () => {
    setVerifyError(null);
    setVerifying(true);
    api.post('/cart/verify', { items: cart })
      .then(() => setVerifyError(null))
      .catch(() => setVerifyError('Verification failed again. Please remove unavailable items or try later.'))
      .finally(() => setVerifying(false));
  };

  return (
    <div className="spa-page">
      <div className="spa-container">
        <div className="spa-page__header">
          <h1 className="spa-page__title">Your Cart</h1>
        </div>

        {itemCount === 0 ? (
          <div className="spa-card spa-card--padded spa-empty">
            <div className="spa-empty__icon" aria-hidden="true">🛒</div>
            <h2 className="spa-empty__title">Cart is empty</h2>
            <p className="spa-empty__text">
              Your cart doesn't have any items. <Link to="/events">Browse events</Link> to add tickets to your cart.
            </p>
          </div>
        ) : (
          <div className="spa-card spa-card--padded">
            <h2 style={{ margin: '0 0 16px', fontSize: '1.125rem', fontWeight: 700 }}>Cart Items</h2>

            {verifyError && (
              <div className="spa-alert spa-alert--warning">
                {verifyError}
                <button type="button" onClick={handleRetry} className="spa-btn spa-btn--secondary" style={{ marginLeft: 12, padding: '4px 12px', fontSize: 12 }}>
                  Retry
                </button>
              </div>
            )}

            <p style={{ fontSize: 14, color: '#666', marginBottom: 16 }}>
              <strong>Items: {itemCount}</strong>
              <span style={{ marginLeft: 8, color: '#999' }}>{itemCount === 1 ? 'item' : 'items'}</span>
            </p>

            <ul style={{ listStyle: 'none', padding: 0, margin: 0 }}>
              {cart.map((item, index) => (
                <li key={index} style={{ display: 'flex', alignItems: 'center', gap: 12, padding: '12px 0', borderBottom: '1px solid #E3E4E6' }}>
                  <span style={{ fontWeight: 500 }}>{item.name || item.title || 'Item'}</span>
                  <span style={{ color: '#999' }}>{item.quantity || 1}x</span>
                  <button onClick={() => removeFromCart(item.id)} className="spa-btn spa-btn--danger" style={{ marginLeft: 'auto', padding: '4px 12px', fontSize: 12 }}>
                    Remove
                  </button>
                </li>
              ))}
            </ul>

            <div style={{ marginTop: 16, paddingTop: 16, borderTop: '1px solid #E3E4E6' }}>
              <p style={{ fontSize: 14, color: '#666', marginBottom: 16 }}>Total: {itemCount} item(s)</p>
              <button
                onClick={() => navigate('/checkout')}
                className="spa-btn spa-btn--primary"
                disabled={verifying}
              >
                Proceed to Checkout →
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default CartPage;
