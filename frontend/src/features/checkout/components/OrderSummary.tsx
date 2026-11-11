import React from 'react';
import { formatCurrency, formatFeeLine } from '../../../lib/currencyUtils';

interface OrderItem {
  readonly id: string;
  readonly ticketTierId: string;
  readonly quantity: number;
  readonly unitPrice: number;
  readonly tierName?: string;
}

interface FeeBreakdownItem {
  readonly label: string;
  readonly amount: number;
}

interface OrderSummaryProps {
  items: OrderItem[];
  subtotal: number;
  fees: number;
  tax?: number;
  total: number;
  currency: string;
  feeBreakdown?: FeeBreakdownItem[];
  locale?: string;
  isLoading?: boolean;
  error?: string | null;
}

const OrderSummary: React.FC<OrderSummaryProps> = ({
  items,
  subtotal,
  fees,
  tax = 0,
  total,
  currency,
  feeBreakdown,
  locale = 'en-US',
  isLoading = false,
  error = null,
}) => {
  const formattedSubtotal = formatCurrency(subtotal, currency, locale);
  const formattedFees = formatCurrency(fees, currency, locale);
  const formattedTax = formatCurrency(tax, currency, locale);
  const formattedTotal = formatCurrency(total, currency, locale);

  const breakdown = feeBreakdown?.length
    ? feeBreakdown
    : [
        { label: 'Service fee', amount: fees },
        tax > 0 ? { label: 'Tax', amount: tax } : null,
      ].filter(Boolean) as FeeBreakdownItem[];

  if (isLoading) {
    return (
      <div
        className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm"
        aria-busy="true"
      >
        <h2 className="text-xl font-bold text-slate-900 mb-6">
          Order Summary
        </h2>
        <div className="space-y-3" role="status" aria-label="Loading order summary">
          {[1, 2, 3].map((i) => (
            <div
              key={i}
              className="flex justify-between items-baseline animate-pulse"
            >
              <span className="text-slate-600 text-sm bg-slate-100 rounded h-5 w-24" />
              <span className="text-slate-900 font-bold bg-slate-100 rounded h-5 w-24" />
            </div>
          ))}
        </div>
        <div className="mt-6 pt-6 border-t border-slate-200">
          <span className="text-slate-600 text-sm">Total</span>
          <span
            className="text-2xl font-bold text-slate-900 bg-slate-100 rounded h-10 w-32"
          />
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div
        className="bg-white rounded-xl border border-red-200 p-6 shadow-sm"
        role="alert"
        aria-live="polite"
      >
        <h2 className="text-xl font-bold text-slate-900 mb-6">
          Order Summary
        </h2>
        <p className="text-sm text-red-700 mb-4">{error}</p>
        <button
          type="button"
          className="text-sm text-indigo-600 hover:underline font-medium"
          aria-label="Retry order summary"
        >
          Retry
        </button>
      </div>
    );
  }

  return (
    <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
      <h2 className="text-xl font-bold text-slate-900 mb-6">
        Order Summary
      </h2>

      {items.length > 0 ? (
        <div className="space-y-4 mb-6">
          {items.map((item) => (
            <div
              key={item.id}
              className="flex justify-between items-baseline group"
            >
              <div className="flex-1 min-w-0">
                <span className="block text-slate-700">
                  {item.quantity}x {item.tierName || item.ticketTierId}
                </span>
                <span className="block text-xs text-slate-500 truncate">
                  {item.unitPrice > 0
                    ? formatCurrency(item.unitPrice, currency, locale) + ' each'
                    : ''}
                </span>
              </div>
              <span className="text-slate-900 font-medium tabular-nums">
                {formatCurrency(
                  item.quantity * item.unitPrice,
                  currency,
                  locale,
                )}
              </span>
            </div>
          ))}
        </div>
      ) : (
        <p className="text-slate-500 mb-6">No items</p>
      )}

      <div className="space-y-2 mb-6">
        <div className="flex justify-between text-sm">
          <span className="text-slate-600">Subtotal</span>
          <span className="text-slate-900 font-medium tabular-nums">
            {formattedSubtotal}
          </span>
        </div>
        {breakdown.map((fee) => (
          <div
            key={fee.label}
            className="flex justify-between text-sm text-slate-600"
          >
            <span>{fee.label}</span>
            <span className="tabular-nums">{formatCurrency(fee.amount, currency, locale)}</span>
          </div>
        ))}
      </div>

      <div className="mt-6 pt-6 border-t border-slate-200">
        <div className="flex justify-between">
          <span className="text-slate-600 text-sm">Total</span>
          <span className="text-2xl font-bold text-slate-900 tabular-nums">
            {formattedTotal}
          </span>
        </div>
      </div>
    </div>
  );
};

export default OrderSummary;