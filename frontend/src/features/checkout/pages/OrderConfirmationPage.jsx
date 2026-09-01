import React, { useEffect, useState } from 'react';
import { Link, useParams, useNavigate } from 'react-router-dom';
import { api } from '../../../lib/api';
import { useAuthContext } from '../../../features/auth/context/AuthContext';

const OrderConfirmationPage = () => {
  const { orderId } = useParams();
  const navigate = useNavigate();
  const { user } = useAuthContext();
  const [order, setOrder] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Fetch order details on mount
  useEffect(() => {
    async function fetchOrder() {
      if (!orderId) {
        setError('Order ID not specified');
        setLoading(false);
        return;
      }

      setLoading(true);
      setError(null);

      try {
        const res = await api.get(`/orders/${orderId}`);
        setOrder(res.data.data || null);
      } catch (err) {
        const status = err?.response?.status;
        setError(
          status === 404 ? 'Order not found' : status === 403 ? 'Permission denied' : 'Failed to load order'
        );
      }
      setLoading(false);
    }

    fetchOrder();
  }, [orderId, user]);

  if (loading) {
    return <div className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="text-center py-12">
        <div className="spinner md:spinner-xl" />
        <p className="text-slate-500 mt-4">Loading order details...</p>
      </div>
    </div>;
  }

  if (error) {
    return (
      <div className="min-h-screen bg-slate-50 p-6 md:p-10">
        <div className="mx-auto max-w-3xl bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
          <h2 className="text-xl font-bold text-slate-900 mb-2">Unable to Load Order</h2>
          <p className="text-slate-500 mb-4">{error}</p>
          <button
            onClick={() => navigate('/events')}
            className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium shadow-sm hover:bg-indigo-700 transition-colors"
          >
            ← Back to Events
          </button>
        </div>
      </div>
    );
  }

  if (order?.status !== 'completed') {
    const paymentPending = order?.status === 'pending';

    return (
      <div className="min-h-screen bg-slate-50 p-6 md:p-10">
        <div className="mx-auto max-w-3xl bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
          <h1 className="text-2xl font-bold text-slate-900 mb-2">
            {paymentPending ? 'Payment Confirmation Pending' : 'Payment Was Not Completed'}
          </h1>
          <p className="text-slate-500 mb-4">
            {paymentPending
              ? 'We are waiting for confirmation from the payment provider. Your tickets will be available once payment succeeds.'
              : 'Your payment was not completed. Your cart is still available if you would like to try again.'}
          </p>
          <div className="flex gap-3">
            {paymentPending ? (
              <Link
                to={`/checkout/callback?orderId=${encodeURIComponent(orderId)}`}
                className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium"
              >
                Check Payment Status
              </Link>
            ) : (
              <Link
                to="/cart"
                className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium"
              >
                Return to Cart
              </Link>
            )}
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="mx-auto max-w-3xl">
        <h1 className="text-2xl font-bold text-slate-900 mb-4">Order Confirmation #{orderId}</h1>
        <p className="text-slate-500 mb-6">
          Your order has been successfully processed. Below are your order details:
        </p>

        {order && (
          <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
            <h3 className="text-lg font-bold text-slate-800 mb-4">Order Details</h3>
            <p className="text-sm text-slate-500 mb-2">
              <strong>Order ID:</strong> {orderId}
            </p>
            <p className="text-sm text-slate-500 mb-2">
              <strong>Status:</strong> Confirmed
            </p>
            <p className="text-sm text-slate-500 mb-2">
              <strong>Total:</strong> {order.currency || 'NGN'} {order.total_amount ?? 0}
            </p>
            <p className="text-sm text-slate-500 mb-2">
              <strong>Tickets:</strong> {order.ticket_count ?? 0}
            </p>
            <div className="mt-4 pt-4 border-t border-slate-200">
              <p className="text-sm text-slate-500">Your tickets will be delivered to your email.</p>
              <button
                onClick={() => navigate('/my-tickets')}
                className="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium shadow-sm hover:bg-indigo-700 transition-colors"
              >
                View My Tickets
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default OrderConfirmationPage;