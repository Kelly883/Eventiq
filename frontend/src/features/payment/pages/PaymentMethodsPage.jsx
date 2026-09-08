import React, { useCallback, useEffect, useState } from 'react';
import { api, showToast } from '../../../lib/api';
import PaymentPageHeader from '../components/PaymentPageHeader';

const GATEWAYS = [
  {
    gateway: 'paystack',
    label: 'Paystack',
    badgeClass: 'bg-sky-50 text-sky-700 ring-sky-200',
  },
  {
    gateway: 'flutterwave',
    label: 'Flutterwave',
    badgeClass: 'bg-fuchsia-50 text-fuchsia-700 ring-fuchsia-200',
  },
];

const TYPE_LABELS = {
  card: 'Card',
  bank_transfer: 'Bank transfer',
  ussd: 'USSD',
  qr: 'QR',
  mobile_money: 'Mobile money',
};

const gatewayLabel = (gateway) => {
  const match = GATEWAYS.find((g) => g.gateway === gateway);
  return match ? match.label : (gateway ?? 'Unknown');
};

const formatMethod = (method) => {
  const typeLabel = TYPE_LABELS[method.type] ?? method.type ?? 'Payment method';
  let primary = typeLabel;
  let secondary = null;

  if (method.type === 'card') {
    const brand = method.brand ? `${method.brand} ` : '';
    primary = brand + (method.lastFour ? `•••• ${method.lastFour}` : typeLabel);
    if (method.expiryMonth && method.expiryYear) {
      secondary = `Expires ${String(method.expiryMonth).padStart(2, '0')}/${method.expiryYear}`;
    }
  } else if (method.lastFour) {
    primary = `${typeLabel} · ${method.lastFour}`;
  }

  return { primary, secondary };
};

export default function PaymentMethodsPage() {
  const [methods, setMethods] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [busyId, setBusyId] = useState(null);

  const fetchMethods = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await api.get('/user/payment-methods');
      setMethods(response.data.data ?? []);
    } catch (err) {
      const description =
        err?.response?.data?.message || err?.message || 'Something went wrong loading your payment methods.';
      setError(description);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchMethods();
  }, [fetchMethods]);

  const handleSetDefault = async (id) => {
    setBusyId(id);
    try {
      await api.post(`/user/payment-methods/${id}/set-default`);
      showToast('Default updated', 'This payment method is now your default.', 'success');
      await fetchMethods();
    } catch (err) {
      showToast('Failed to update default', err?.response?.data?.message || err?.message || 'Please try again.', 'error');
    } finally {
      setBusyId(null);
    }
  };

  const handleRemove = async (id) => {
    if (!window.confirm('Remove this payment method?')) {
      return;
    }
    setBusyId(id);
    try {
      await api.delete(`/user/payment-methods/${id}`);
      showToast('Payment method removed', 'It has been removed from your account.', 'success');
      await fetchMethods();
    } catch (err) {
      showToast('Failed to remove', err?.response?.data?.message || err?.message || 'Please try again.', 'error');
    } finally {
      setBusyId(null);
    }
  };

  return (
    <div>
      <PaymentPageHeader
        title="Payment Methods"
        subtitle="Manage the cards and payment options you use to pay for Eventiq orders."
        crumbs={[
          { label: 'Settings', to: '/settings' },
          { label: 'Payment Methods' },
        ]}
        backTo="/settings"
        backLabel="Back to Settings"
      />

      <div className="rounded-xl border border-slate-200 bg-white p-5 mb-6">
        <h3 className="text-sm font-semibold text-slate-900">Supported gateways</h3>
        <p className="mt-1 text-xs text-slate-500">
          Eventiq accepts payments through Paystack and Flutterwave. New cards, bank transfers, and mobile
          money methods are saved automatically when you pay at checkout.
        </p>
        <div className="mt-3 flex flex-wrap gap-2">
          {GATEWAYS.map((g) => (
            <span
              key={g.gateway}
              className={`inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset ${g.badgeClass}`}
            >
              {g.label}
            </span>
          ))}
        </div>
      </div>

      {loading ? (
        <div className="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-500" role="status">
          Loading your payment methods…
        </div>
      ) : error ? (
        <div className="rounded-xl border border-rose-200 bg-rose-50 p-5">
          <p className="text-sm font-medium text-rose-900">{error}</p>
          <button
            type="button"
            onClick={fetchMethods}
            className="mt-3 rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700 transition-colors"
          >
            Try again
          </button>
        </div>
      ) : methods.length === 0 ? (
        <div className="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-500">
          No saved payment methods yet. Cards and payment options you use at checkout will appear here.
        </div>
      ) : (
        <ul className="space-y-3" aria-label="Saved payment methods">
          {methods.map((method) => {
            const { primary, secondary } = formatMethod(method);
            const isBusy = busyId === method.id;
            return (
              <li key={method.id} className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div className="flex flex-wrap items-center justify-between gap-3">
                  <div className="flex items-center gap-3">
                    <span
                      className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset ${
                        GATEWAYS.find((g) => g.gateway === method.gateway)?.badgeClass ?? 'bg-slate-100 text-slate-700 ring-slate-200'
                      }`}
                    >
                      {gatewayLabel(method.gateway)}
                    </span>
                    <div>
                      <div className="text-sm font-semibold text-slate-900">{primary}</div>
                      {secondary && <div className="text-xs text-slate-500">{secondary}</div>}
                    </div>
                    {method.isDefault && (
                      <span className="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                        Default
                      </span>
                    )}
                    {method.isExpired && (
                      <span className="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">
                        Expired
                      </span>
                    )}
                  </div>
                  <div className="flex items-center gap-2">
                    {!method.isDefault && (
                      <button
                        type="button"
                        onClick={() => handleSetDefault(method.id)}
                        disabled={isBusy}
                        className="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 disabled:opacity-50 transition-colors"
                      >
                        Set as default
                      </button>
                    )}
                    <button
                      type="button"
                      onClick={() => handleRemove(method.id)}
                      disabled={isBusy}
                      className="rounded-lg px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 disabled:opacity-50 transition-colors"
                    >
                      Remove
                    </button>
                  </div>
                </div>
              </li>
            );
          })}
        </ul>
      )}
    </div>
  );
}