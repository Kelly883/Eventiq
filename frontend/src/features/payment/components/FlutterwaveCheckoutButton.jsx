import React from 'react';
import { useFlutterwave, closePaymentModal } from 'flutterwave-react-v3';
import { flutterwaveConfig, isFlutterwaveConfigured } from '../utils/flutterwaveConfig';

/**
 * Triggers the Flutterwave inline popup. Amount is expected as a whole
 * number in the major currency unit (e.g. naira for NGN).
 */
export default function FlutterwaveCheckoutButton({ email, amount, reference, onSuccess }) {
  const config = {
    public_key: flutterwaveConfig.publicKey,
    tx_ref: reference,
    amount,
    currency: flutterwaveConfig.currency,
    payment_options: 'card,mobilemoney,ussd',
    customer: { email },
  };

  const handleFlutterPayment = useFlutterwave(config);

  if (!isFlutterwaveConfigured()) {
    return <button type="button" disabled title="VITE_FLUTTERWAVE_PUBLIC_KEY is not set">Pay with Flutterwave</button>;
  }

  return (
    <button
      type="button"
      onClick={() =>
        handleFlutterPayment({
          callback: (response) => {
            onSuccess?.(response);
            closePaymentModal();
          },
          onClose: () => {},
        })
      }
    >
      Pay with Flutterwave
    </button>
  );
}
