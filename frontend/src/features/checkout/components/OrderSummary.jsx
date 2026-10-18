import React from 'react';
import { formatCurrency } from '../../lib/currencyUtils';

interface OrderItem {
  readonly id: string;
  readonly ticketTierId: string;
  readonly quantity: number;
  readonly unitPrice: number;
}

interface OrderSummaryProps {
  items: OrderItem[];
  subtotal: number;
  fees: number;
  total: number;
  currency: string;
}

const OrderSummary: OrderSummaryProps = ({ items, subtotal, fees, total, currency }) => {
  return (
    <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
      <h2 className="text-xl font-bold text-slate-900 mb-6">Order Summary</h2>

      {items.length > 0 ? (
        <div className="space-y-4 mb-6">
          {items.map((item) => (
            <div key={item.id} className="flex justify-between items-baseline">
              <span className="text-slate-700">
                {item.quantity}x —
              </span>
              <span>{item.quantity * item.unitPrice}</span>
            </div>
          ))}
        </div>
      ) : (
        <p className="text-slate-500">No items</p>
      )}

      <div className="grid grid-cols-2 gap-4 mb-6">
        <div>
          <span className="text-slate-600 text-sm">Subtotal</span>
          <span className="text-slate-900 font-bold">{formatCurrency(subtotal, currency)}</span>
        </div>
        <div>
          <span className="text-slate-600 text-sm">Fees</span>
          <span className="text-slate-900 font-bold">{formatCurrency(fees, currency)}</span>
        </div>
      </div>

      <div className="mt-6 pt-6 border-t border-slate-200">
        <span className="text-slate-600 text-sm">Total</span>
        <span className="text-2xl font-bold text-slate-900">{formatCurrency(total, currency)}</span>
      </div>
    </div>
  );
};

export default OrderSummary;