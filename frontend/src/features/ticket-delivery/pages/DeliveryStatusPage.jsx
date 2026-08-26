import React, { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { api } from '../../../lib/api';

const DeliveryStatusPage = () => {
  const { ticketId } = useParams();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [ticket, setTicket] = useState(null);
  const [resendingChannel, setResendingChannel] = useState(null);
  const [toast, setToast] = useState(null);

  useEffect(() => {
    if (!ticketId) return;

    const fetchTicket = async () => {
      setLoading(true);
      setError(null);
      try {
        const response = await api.get(`/tickets/${encodeURIComponent(ticketId)}`);
        if (response?.data) {
          setTicket(response.data);
        } else {
          setError(`No ticket found for code "${ticketId}".`);
        }
      } catch (err) {
        const status = err?.response?.status;
        setError(
          status === 404
            ? `No ticket found for code "${ticketId}".`
            : 'Could not retrieve ticket. Please try again.'
        );
      } finally {
        setLoading(false);
      }
    };

    fetchTicket();
  }, [ticketId]);

  const showToast = (message, type = 'success') => {
    setToast({ message, type });
    setTimeout(() => setToast(null), 4000);
  };

  const triggerRedelivery = async (channel) => {
    if (!ticket?.code) return;
    setResendingChannel(channel);
    try {
      const endpoint = channel === 'email' ? 'resend-email' : channel === 'sms' ? 'resend-sms' : 'resend-dashboard';
      await api.post(`/tickets/${encodeURIComponent(ticket.code)}/${endpoint}`);
      showToast(`Ticket re-delivered via ${channel.toUpperCase()}!`);
    } catch {
      showToast(`Failed to re-deliver via ${channel.toUpperCase()}.`, 'error');
    } finally {
      setResendingChannel(null);
    }
  };

  return (
    <div className="min-h-screen bg-slate-50 py-10 px-4 sm:px-6 lg:px-8 font-sans">
      {toast && (
        <div
          className={`fixed top-5 right-5 z-50 flex items-center p-4 rounded-lg shadow-lg border transition-all duration-300 ${
            toast.type === 'error'
              ? 'bg-red-50 border-red-200 text-red-800'
              : 'bg-emerald-50 border-emerald-200 text-emerald-800'
          }`}
        >
          <span className="font-medium mr-2">{toast.type === 'error' ? '✕' : '✓'}</span>
          <span>{toast.message}</span>
        </div>
      )}

      <div className="max-w-4xl mx-auto">
        <div className="mb-6">
          <Link to="/my-tickets" className="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-900">
            ← Back to My Tickets
          </Link>
        </div>

        <div className="text-center mb-10">
          <h1 className="text-4xl font-extrabold text-slate-900 tracking-tight mb-2">
            Delivery Status
          </h1>
          <p className="text-lg text-slate-600">
            Track delivery for ticket: <span className="font-mono font-semibold">{ticketId}</span>
          </p>
        </div>

        {loading && (
          <div className="bg-white rounded-2xl shadow-sm border border-slate-200 p-12 text-center">
            <div className="text-4xl mb-4">⏳</div>
            <p className="text-slate-600">Loading ticket details...</p>
          </div>
        )}

        {error && !loading && (
          <div className="bg-red-50 text-red-700 rounded-xl border border-red-200 p-6 text-center">
            <p className="font-medium">{error}</p>
            <Link to="/my-tickets" className="mt-4 inline-block text-sm font-semibold text-red-600 hover:text-red-800">
              ← Back to My Tickets
            </Link>
          </div>
        )}

        {ticket && !loading && (
          <div className="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h2 className="text-lg font-bold text-slate-900 mb-4">Delivery Channels</h2>
            <div className="space-y-4">
              {['email', 'sms', 'dashboard'].map((channel) => {
                const status = ticket.deliveryStatus?.[channel] || { sent: false, recipient: 'N/A', timestamp: null };
                return (
                  <div key={channel} className="flex items-center justify-between p-4 rounded-xl border border-slate-200 hover:bg-slate-50">
                    <div className="flex items-center gap-3">
                      <div className="p-2 bg-slate-100 rounded-lg text-lg">
                        {channel === 'email' ? '✉️' : channel === 'sms' ? '📱' : '🖥️'}
                      </div>
                      <div>
                        <h4 className="text-sm font-semibold text-slate-800 capitalize">{channel}</h4>
                        <p className="text-xs text-slate-500">{status.recipient}</p>
                        {status.timestamp && (
                          <p className="text-xs text-slate-400">Receipt: {status.timestamp}</p>
                        )}
                      </div>
                    </div>
                    <div className="flex items-center gap-3">
                      <span className={`px-2 py-1 rounded text-xs font-bold ${
                        status.sent
                          ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                          : 'bg-amber-50 text-amber-700 border border-amber-200'
                      }`}>
                        {status.sent ? 'Dispatched' : 'Pending'}
                      </span>
                      <button
                        onClick={() => triggerRedelivery(channel)}
                        disabled={resendingChannel !== null}
                        className="text-xs font-bold text-indigo-600 hover:text-indigo-800 disabled:opacity-50"
                      >
                        {resendingChannel === channel ? 'Sending...' : 'Resend'}
                      </button>
                    </div>
                  </div>
                );
              })}
            </div>

            <div className="mt-6 pt-6 border-t border-slate-200">
              <Link
                to={`/my-tickets/${ticketId}/status`}
                className="text-sm font-medium text-indigo-600 hover:text-indigo-800"
              >
                View full ticket status →
              </Link>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default DeliveryStatusPage;
