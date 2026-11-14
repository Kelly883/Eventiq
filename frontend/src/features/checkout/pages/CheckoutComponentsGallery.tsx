import React, { useCallback, useState, useEffect } from 'react';
import {
  CartItemRow,
  OrderConfirmation,
  OrderSummary,
  PaymentForm,
  QRCodeDisplay,
  TicketCard,
} from '../components';

type LogEntry = { name: string; payload: unknown };

declare global {
  interface Window {
    __checkoutGalleryLog?: LogEntry[];
  }
}

function log(name: string, payload: unknown) {
  if (typeof window === 'undefined') return;
  window.__checkoutGalleryLog = window.__checkoutGalleryLog ?? [];
  window.__checkoutGalleryLog.push({ name, payload });
  window.dispatchEvent(new CustomEvent('checkout-gallery:log'));
}

const ticket = {
  id: 'tick_123',
  orderId: 'order_123',
  eventId: 'evt_1',
  eventTitle: 'Jazz Night Live',
  eventDate: '2026-12-15T19:00:00Z',
  eventVenue: 'The Blue Note, New York',
  qrCodeData: 'TICKET|tick_123|valid|2026-12-15',
  status: 'valid',
};

const eventInfo = {
  title: 'Jazz Night Live',
  date: '2026-12-15T19:00:00Z',
  venue: 'The Blue Note, New York',
};

const ticketTier = {
  id: 1,
  name: 'VIP',
  price: 15000,
  currency: 'USD',
};

const cartItem = {
  ticketTierId: 't1',
  quantity: 2,
  unitPrice: 2500,
  eventTitle: 'Rock Fest 2026',
  eventDate: '2026-11-20T20:00:00Z',
  eventVenue: 'Mega Arena, Lagos',
  tierName: 'General Admission',
  currency: 'USD',
};

const orderItems = [
  { id: 'i1', ticketTierId: 't1', quantity: 2, unitPrice: 2500, tierName: 'General Admission' },
  { id: 'i2', ticketTierId: 't2', quantity: 1, unitPrice: 4000, tierName: 'VIP' },
];

export default function CheckoutComponentsGallery() {
  const [qty, setQty] = useState(2);
  const [payLoading, setPayLoading] = useState(false);
  const [stage, setStage] = useState<'form' | 'confirmation'>('form');

  const handleQtyChange = useCallback((newQty: number) => {
    log('cart:quantityChange', { newQty });
    setQty(newQty);
  }, []);

  const handleRemove = useCallback(() => {
    log('cart:remove');
  }, []);

  const handlePaySuccess = useCallback(
    (response: unknown) => {
      log('payment:success', response);
      setPayLoading(false);
      setStage('confirmation');
    },
    [],
  );

  const handlePayError = useCallback((error: string) => {
    log('payment:error', error);
    setPayLoading(false);
  }, []);

  // Runtime accessibility audit via react-axe (development only)
  useEffect(() => {
    if (import.meta.env.DEV) {
      const axe = require('react-axe');
      const { React, ReactDOM } = require('react');
      const root = ReactDOM.createRoot(document.body);
      axe(React, ReactDOM, 1000).then((results) => {
        if (results && results.length > 0) {
          console.warn('[react-axe] Accessibility violations:', results);
        }
      });
    }
    return () => {};
  }, []);

  return (
    <div className="max-w-5xl mx-auto p-6 space-y-12 font-sans">
      <h1 className="text-3xl font-bold text-slate-900">Checkout Components</h1>
      <p className="text-slate-600">
        Interactive gallery for the Eventiq checkout components. Every
        interaction is logged to <code>window.__checkoutGalleryLog</code> and
        dispatched as a <code>checkout-gallery:log</code> event.
      </p>

      {/* OrderSummary */}
      <section aria-labelledby="sec-order-summary" className="border border-slate-200 rounded-xl p-6">
        <h2 id="sec-order-summary" className="text-lg font-bold text-slate-900 mb-4">
          OrderSummary
        </h2>
        <OrderSummary
          items={orderItems}
          subtotal={9000}
          fees={1500}
          tax={900}
          total={11400}
          currency="USD"
          feeBreakdown={[
            { label: 'Service fee', amount: 1200 },
            { label: 'Tax', amount: 300 },
          ]}
        />
      </section>

      {/* CartItemRow */}
      <section aria-labelledby="sec-cart-item" className="border border-slate-200 rounded-xl p-6">
        <h2 id="sec-cart-item" className="text-lg font-bold text-slate-900 mb-4">
          CartItemRow
        </h2>
        <CartItemRow
          item={cartItem}
          quantity={qty}
          onQuantityChange={handleQtyChange}
          onRemove={handleRemove}
        />
      </section>

      {/* TicketCard */}
      <section aria-labelledby="sec-ticket-card" className="border border-slate-200 rounded-xl p-6">
        <h2 id="sec-ticket-card" className="text-lg font-bold text-slate-900 mb-4">
          TicketCard
        </h2>
        <TicketCard
          ticket={ticket}
          event={eventInfo}
          ticketTier={ticketTier}
        />
      </section>

      {/* QRCodeDisplay */}
      <section aria-labelledby="sec-qr" className="border border-slate-200 rounded-xl p-6">
        <h2 id="sec-qr" className="text-lg font-bold text-slate-900 mb-4">
          QRCodeDisplay
        </h2>
        <div className="flex gap-4">
          <QRCodeDisplay
            data="https://eventiq.test/ticket/abc-123"
            size={160}
            label="Ticket QR"
          />
          <QRCodeDisplay
            data="https://eventiq.test/ticket/abc-123"
            size={80}
            label="Small QR"
            lazy
          />
          <QRCodeDisplay data="" label="Empty" />
        </div>
      </section>

      {/* PaymentForm */}
      <section aria-labelledby="sec-payment" className="border border-slate-200 rounded-xl p-6">
        <h2 id="sec-payment" className="text-lg font-bold text-slate-900 mb-4">
          PaymentForm
        </h2>
        <PaymentForm
          clientSecret="pi_test_123_secret"
          onSuccess={handlePaySuccess}
          onError={handlePayError}
          isLoading={payLoading}
          gateway="paystack"
          amount={11400}
          currency="USD"
          email="buyer@example.com"
          reference="ref_123"
        />
        <p className="mt-2 text-xs text-slate-500">
          Gateway buttons are rendered for Paystack / Flutterwave (this
          codebase has no Stripe dependency). Loading state disables the button
          and shows &ldquo;Processing&hellip;&rdquo;.
        </p>
      </section>

      {/* OrderConfirmation */}
      <section aria-labelledby="sec-confirmation" className="border border-slate-200 rounded-xl p-6">
        <h2 id="sec-confirmation" className="text-lg font-bold text-slate-900 mb-4">
          OrderConfirmation
        </h2>
        {stage === 'confirmation' ? (
          <OrderConfirmation
            orderId="order_123"
            total={11400}
            currency="USD"
            status="success"
            onContinueShopping={() => log('confirmation:continue')}
            onViewTickets={() => log('confirmation:viewTickets')}
          />
        ) : (
          <button
            type="button"
            onClick={() => setStage('confirmation')}
            className="bg-indigo-600 text-white px-4 py-2 rounded-md font-medium hover:bg-indigo-700"
          >
            Simulate successful payment
          </button>
        )}
      </section>

      {/* Callback log */}
      <section aria-labelledby="sec-log" className="border border-slate-200 rounded-xl p-6">
        <h2 id="sec-log" className="text-lg font-bold text-slate-900 mb-4">
          Callback log
        </h2>
        <pre
          data-testid="checkout-cb-log"
          className="bg-slate-900 text-slate-300 text-xs rounded p-4 overflow-auto max-h-40 font-mono"
        >
{JSON.stringify(window.__checkoutGalleryLog ?? [], null, 2)}
        </pre>
      </section>
    </div>
  );
}