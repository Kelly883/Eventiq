import React, { useCallback, useEffect, useState } from 'react';
import { Link, useParams, useNavigate } from 'react-router-dom';
import { api } from '../../../lib/api';
import { useAuthContext } from '../../../features/auth/context/AuthContext';
import { OrderConfirmationSkeleton } from '../components/Skeletons';
import OrderConfirmation from '../components/OrderConfirmation';

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

  const handleContinueShopping = useCallback(() => navigate('/events'), [navigate]);
  const handleViewTickets = useCallback(() => navigate('/my-tickets'), [navigate]);

  if (loading) {
    return <OrderConfirmationSkeleton />;
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

  if (!order) {
    return (
      <div className="min-h-screen bg-slate-50 p-6 md:p-10">
        <div className="mx-auto max-w-3xl bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
          <h2 className="text-xl font-bold text-slate-900 mb-2">Order Not Found</h2>
          <p className="text-slate-500 mb-4">We could not find this order.</p>
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

  const status = order.status === 'completed' ? 'success' : order.status === 'failed' ? 'failed' : 'pending';

  return (
    <div className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="mx-auto max-w-2xl">
        <OrderConfirmation
          orderId={order.id}
          total={Number(order.total_amount ?? 0)}
          currency={order.currency || 'USD'}
          status={status}
          onContinueShopping={handleContinueShopping}
          onViewTickets={handleViewTickets}
        />
      </div>
    </div>
  );
};

export default OrderConfirmationPage;