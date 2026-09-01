import React, { useCallback, useEffect, useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { api } from '../../../lib/api';
import { useCartContext } from '../context/CartContext';

const POLL_INTERVAL_MS = 3000;

const CheckoutCallbackPage = () => {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const { clearCart } = useCartContext();
  const orderId = searchParams.get('orderId');
  const [order, setOrder] = useState(null);
  const [error, setError] = useState('');

  const fetchOrderStatus = useCallback(async () => {
    if (!orderId) {
      setError('We could not identify the order that was paid for.');
      return;
    }

    try {
      const response = await api.get(`/orders/${encodeURIComponent(orderId)}`);
      setOrder(response.data.data);
      setError('');
    } catch (requestError) {
      const status = requestError.response?.status;
      setError(status === 404 ? 'This order was not found or is not available to your account.' : 'We could not check this payment yet.');
    }
  }, [orderId]);

  useEffect(() => {
    fetchOrderStatus();
  }, [fetchOrderStatus]);

  useEffect(() => {
    if (!order || order.status !== 'pending') return undefined;

    const interval = window.setInterval(fetchOrderStatus, POLL_INTERVAL_MS);
    return () => window.clearInterval(interval);
  }, [fetchOrderStatus, order]);

  useEffect(() => {
    if (order?.status !== 'completed') return;
    clearCart();
    navigate(`/order/${encodeURIComponent(order.id)}/confirmation`, { replace: true });
  }, [clearCart, navigate, order]);

  if (error) {
    return (
      <div className="min-h-screen bg-slate-50 p-6 md:p-10">
        <div className="mx-auto max-w-2xl bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
          <h1 className="text-2xl font-bold text-slate-900 mb-2">Unable to Confirm Payment</h1>
          <p className="text-slate-500 mb-4">{error}</p>
          <Link to="/cart" className="inline-flex px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium">
            Return to Cart
          </Link>
        </div>
      </div>
    );
  }

  if (!order || order.status === 'pending') {
    return (
      <div className="min-h-screen bg-slate-50 p-6 md:p-10">
        <div className="mx-auto max-w-2xl bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
          <h1 className="text-2xl font-bold text-slate-900 mb-2">Confirming Your Payment</h1>
          <p className="text-slate-500">
            We are verifying the payment with the provider. This page will update automatically when confirmation arrives.
          </p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="mx-auto max-w-2xl bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
        <h1 className="text-2xl font-bold text-slate-900 mb-2">Payment Was Not Completed</h1>
        <p className="text-slate-500 mb-4">
          The payment provider reported that this payment did not complete. Your cart has been kept so you can try again.
        </p>
        <Link to="/cart" className="inline-flex px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium">
          Return to Cart
        </Link>
      </div>
    </div>
  );
};

export default CheckoutCallbackPage;
