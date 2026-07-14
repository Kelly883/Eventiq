import React from 'react';
import { usePaystackPayment } from 'react-paystack';
import { paystackConfig, isPaystackConfigured } from '../utils/paystackConfig';

/**
 * Triggers the Paystack inline popup. Amount is expected in the smallest
 * currency unit (kobo for NGN), per Paystack's API.
 */
export default function PaystackCheckoutButton({ email, amountKobo, reference, onSuccess, onClose }) {
  const initializePayment = usePaystackPayment({
    publicKey: paystackConfig.publicKey,
    email,
    amount: amountKobo,
    currency: paystackConfig.currency,
    reference,
  });

  if (!isPaystackConfigured()) {
    return <button type="button" disabled title="VITE_PAYSTACK_PUBLIC_KEY is not set">Pay with Paystack</button>;
  }

  return (
    <button
      type="button"
      onClick={() => initializePayment({ onSuccess, onClose })}
    >
      Pay with Paystack
    </button>
  );
}
