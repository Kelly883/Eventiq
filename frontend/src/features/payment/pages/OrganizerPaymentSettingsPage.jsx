import React, { useCallback, useEffect, useState } from 'react';
import PaymentPageHeader from '../components/PaymentPageHeader';
import { useOrganizerPayoutSettings } from '../hooks/useOrganizerPayoutSettings';

const GATEWAY_DEFS = {
  paystack: {
    name: 'Paystack',
    badge: 'bg-sky-50 text-sky-700 ring-sky-200',
    statusKey: 'paystackConnectStatus',
    businessNameKey: 'paystackBusinessName',
    subaccountCodeKey: 'paystackSubaccountCode',
    recipientCodeKey: 'paystackRecipientCode',
    connectedAtKey: 'paystackConnectedAt',
  },
  flutterwave: {
    name: 'Flutterwave',
    badge: 'bg-fuchsia-50 text-fuchsia-700 ring-fuchsia-200',
    statusKey: 'flutterwaveConnectStatus',
    businessNameKey: 'flutterwaveBusinessName',
    subaccountCodeKey: 'flutterwaveSubaccountId',
    recipientCodeKey: 'flutterwaveBusinessReference',
    connectedAtKey: 'flutterwaveConnectedAt',
  },
};

const STATUS_META = {
  enabled: {
    label: 'Connected & active',
    tone: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    helper: 'Your subaccount is active. Payments are split to your payouts whenever supported.',
  },
  pending: {
    label: 'Pending verification',
    tone: 'bg-amber-50 text-amber-700 ring-amber-200',
    helper: 'Setup is in progress with the gateway. Check back after verification completes.',
  },
  disabled: {
    label: 'Disabled',
    tone: 'bg-rose-50 text-rose-700 ring-rose-200',
    helper: 'Payments to this subaccount are paused. Contact Eventiq support to re-enable.',
  },
  not_connected: {
    label: 'Not connected',
    tone: 'bg-slate-100 text-slate-600 ring-slate-200',
    helper: 'No gateway subaccount is configured yet. Once your onboarding completes, your configuration appears here.',
  },
};

const statusMeta = (status) => STATUS_META[status] ?? STATUS_META.not_connected;

const maskAccountNumber = (value) => {
  if (!value) return '—';
  const digits = String(value).replace(/\D/g, '');
  if (digits.length <= 4) return `••••${digits}`;
  return `••••••${digits.slice(-4)}`;
};

export default function OrganizerPaymentSettingsPage() {
  const [settings, setSettings] = useState(null);
  const [payoutMethods, setPayoutMethods] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const { fetchSettings, fetchPayoutMethods } = useOrganizerPayoutSettings();

  const fetchAll = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const [settingsData, payoutMethodsData] = await Promise.all([
        fetchSettings(),
        fetchPayoutMethods(),
      ]);
      setSettings(settingsData);
      setPayoutMethods(payoutMethodsData);
    } catch (err) {
      const message =
        err?.response?.data?.message || err?.message || 'Something went wrong loading your payment settings.';
      if (err?.response?.status === 403) {
        setError('Your organizer access could not be verified. If this persists, contact support.');
      } else {
        setError(message);
      }
    } finally {
      setLoading(false);
    }
  }, [fetchSettings, fetchPayoutMethods]);

  useEffect(() => {
    fetchAll();
  }, [fetchAll]);

  return (
    <div>
      <PaymentPageHeader
        title="Payment Settings"
        subtitle="Your Paystack and Flutterwave gateway configuration and payout methods."
        crumbs={[
          { label: 'Organizer Settings', to: '/organizer/settings' },
          { label: 'Payment Settings' },
        ]}
        backTo="/organizer/settings"
        backLabel="Back to Organizer Settings"
      />

      <div className="space-y-6">
        <section aria-label="Gateway status">
          <h3 className="mb-3 text-sm font-semibold text-slate-900">Payment gateways</h3>
          {loading ? (
            <div className="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-500" role="status">
              Loading your payment settings…
            </div>
          ) : error ? (
            <div className="rounded-xl border border-rose-200 bg-rose-50 p-5">
              <p className="text-sm font-medium text-rose-900">{error}</p>
              <button
                type="button"
                onClick={fetchAll}
                className="mt-3 rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700 transition-colors"
              >
                Try again
              </button>
            </div>
          ) : (
            <div className="grid gap-4 sm:grid-cols-2">
              {Object.entries(GATEWAY_DEFS).map(([gateway, def]) => {
                const rawStatus = settings?.[def.statusKey] ?? null;
                const normalizedStatus = rawStatus === 'enabled' ? 'enabled' : rawStatus || 'not_connected';
                const meta = statusMeta(normalizedStatus);
                const businessName = settings?.[def.businessNameKey];
                const subaccountCode = settings?.[def.subaccountCodeKey];
                const recipientCode = settings?.[def.recipientCodeKey];
                const connectedAt = settings?.[def.connectedAtKey];

                return (
                  <div key={gateway} className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div className="flex items-center justify-between gap-2">
                      <span
                        className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset ${def.badge}`}
                      >
                        {def.name}
                      </span>
                      <span
                        className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset ${meta.tone}`}
                      >
                        {meta.label}
                      </span>
                    </div>

                    <dl className="mt-4 space-y-2 text-sm">
                      <div className="flex items-center justify-between gap-3">
                        <dt className="text-slate-500">Business name</dt>
                        <dd className="font-semibold text-slate-900">{businessName || '—'}</dd>
                      </div>
                      <div className="flex items-center justify-between gap-3">
                        <dt className="text-slate-500">Subaccount</dt>
                        <dd className="font-mono text-xs text-slate-700">{subaccountCode || '—'}</dd>
                      </div>
                      {recipientCode && def.recipientCodeKey === 'paystackRecipientCode' && (
                        <div className="flex items-center justify-between gap-3">
                          <dt className="text-slate-500">Recipient</dt>
                          <dd className="font-mono text-xs text-slate-700">{recipientCode}</dd>
                        </div>
                      )}
                      {recipientCode && def.recipientCodeKey === 'flutterwaveBusinessReference' && (
                        <div className="flex items-center justify-between gap-3">
                          <dt className="text-slate-500">Reference</dt>
                          <dd className="font-mono text-xs text-slate-700">{recipientCode}</dd>
                        </div>
                      )}
                      {connectedAt && (
                        <div className="flex items-center justify-between gap-3">
                          <dt className="text-slate-500">Connected at</dt>
                          <dd className="text-slate-700">{new Date(connectedAt).toLocaleDateString()}</dd>
                        </div>
                      )}
                    </dl>

                    <p className="mt-4 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500">{meta.helper}</p>
                  </div>
                );
              })}
            </div>
          )}
        </section>

        <section aria-label="Payout methods">
          <div className="mb-3 flex items-center justify-between">
            <h3 className="text-sm font-semibold text-slate-900">Payout bank account</h3>
            <span className="text-xs text-slate-400">Where payouts are sent</span>
          </div>

          {loading ? null : payoutMethods && payoutMethods.length > 0 ? (
            <ul className="space-y-3" aria-label="Payout methods">
              {payoutMethods.map((method) => (
                <li key={method.id} className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                  <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                      <span className="text-lg">🏦</span>
                      <div>
                        <div className="text-sm font-semibold text-slate-900">{method.account_name}</div>
                        <div className="text-xs text-slate-500">
                          {method.bank_name} · {maskAccountNumber(method.account_number)}
                        </div>
                      </div>
                    </div>
                    {method.is_default ? (
                      <span className="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                        Default payout method
                      </span>
                    ) : (
                      <span className="text-xs text-slate-400">Standby</span>
                    )}
                  </div>
                </li>
              ))}
            </ul>
          ) : (
            !loading &&
            !error && (
              <div className="rounded-xl border border-slate-200 bg-white p-6 text-sm text-slate-500">
                No payout method configured yet. Payouts require a verified bank account.
              </div>
            )
          )}
        </section>

        <p className="text-xs text-slate-400">
          Gateway configuration is set through Eventiq onboarding. This page reflects your saved configuration —
          no secrets are ever shown.
        </p>
      </div>
    </div>
  );
}