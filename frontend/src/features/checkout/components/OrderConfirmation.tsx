import React from 'react';
import { formatCurrency } from '../../../lib/currencyUtils';

interface OrderConfirmationProps {
  orderId: string;
  total: number;
  currency: string;
  status: 'success' | 'failed' | 'pending';
  onContinueShopping: () => void;
  onViewTickets: () => void;
}

export default function OrderConfirmation({
  orderId,
  total,
  currency,
  status = 'success',
  onContinueShopping,
  onViewTickets,
}: OrderConfirmationProps) {
  const formattedTotal = formatCurrency(total, currency);
  const succeeded = status === 'success';

  return (
    <div className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="mx-auto max-w-2xl">
        <div className="bg-white rounded-2xl border border-slate-200 p-8 md:p-12 shadow-sm text-center">
          {succeeded ? (
            <>
              <div className="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-6">
                <svg className="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <h2 className="text-2xl font-bold text-slate-900 mb-2">Order placed</h2>
              <p className="text-slate-600 mb-6">
                Your payment was successful. Your tickets are ready.
              </p>
            </>
          ) : status === 'failed' ? (
            <>
              <div className="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-6">
                <svg className="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                </svg>
              </div>
              <h2 className="text-2xl font-bold text-slate-900 mb-2">Payment failed</h2>
              <p className="text-slate-600 mb-6">
                We couldn't complete your payment. Please try again or contact support.
              </p>
            </>
          ) : (
            <>
              <div className="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-amber-100 mb-6">
                <svg className="h-8 w-8 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
              </div>
              <h2 className="text-2xl font-bold text-slate-900 mb-2">Order received</h2>
              <p className="text-slate-600 mb-6">
                We're processing your payment. We'll email you once it's confirmed.
              </p>
            </>
          )}

          <div className="bg-slate-50 rounded-xl border border-slate-100 p-4 mb-8">
            <div className="flex justify-between mb-2">
              <span className="text-slate-600 text-sm">Order total</span>
              <span className="text-slate-900 font-semibold tabular-nums">{formattedTotal}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-slate-600 text-sm">Order reference</span>
              <span className="text-slate-900 font-mono text-sm truncate max-w-[180px]">{orderId}</span>
            </div>
          </div>

          <div className="flex flex-col sm:flex-row gap-3">
            {succeeded && (
              <button
                type="button"
                onClick={onViewTickets}
                className="flex-1 px-5 py-3 rounded-xl font-semibold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 transition-colors"
              >
                View tickets
              </button>
            )}
            <button
              type="button"
              onClick={onContinueShopping}
              className="flex-1 px-5 py-3 rounded-xl font-semibold text-indigo-600 bg-white border border-indigo-600 hover:bg-indigo-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 transition-colors"
            >
              {succeeded ? 'Continue shopping' : 'Try again'}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}
