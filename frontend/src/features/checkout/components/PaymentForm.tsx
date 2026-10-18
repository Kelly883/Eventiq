import React, { useState } from 'react';
import { usePaystackPayment } from 'react-paystack';
import type { InitializePayment } from 'react-paystack/dist/types';
import { paystackConfig, isPaystackConfigured } from '../../payment/utils/paystackConfig';
import { useFlutterwave, type FlutterWaveTypes } from 'flutterwave-react-v3';
import { flutterwaveConfig, isFlutterwaveConfigured } from '../../payment/utils/flutterwaveConfig';

interface PaymentFormProps {
  clientSecret?: string;
  onSuccess: (response: any) => void;
  onError: (error: string) => void;
  isLoading: boolean;
  gateway?: 'paystack' | 'flutterwave';
  amount: number;
  currency: string;
  email: string;
  reference?: string;
}

const PaymentForm: React.FC<PaymentFormProps> = ({
  clientSecret,
  onSuccess,
  onError,
  isLoading,
  gateway = 'paystack',
  amount,
  currency = 'NGN',
  email,
  reference = 'eventiq-checkout',
}) => {
  const [error, setError] = useState<string | null>(null);
  const [errorAnnouncement, setErrorAnnouncement] = useState<string | null>(null);

  const announceError = (message: string) => {
    setError(message);
    setErrorAnnouncement(message);
    onError(message);
    // Clear announcement after screen readers have time to read it
    setTimeout(() => setErrorAnnouncement(null), 1000);
  };

  const initializePaystackPayment: InitializePayment = usePaystackPayment({
    publicKey: paystackConfig.publicKey,
    amount: Math.round(amount * 100), // Paystack expects amount in kobo (smallest unit)
    currency: currency as 'NGN' | 'USD' | 'GHS' | 'ZAR' | 'KES',
    reference,
  });

  const initiateFlutterwavePayment = useFlutterwave({
    public_key: flutterwaveConfig.publicKey,
    tx_ref: reference,
    amount,
    currency,
    payment_options: 'card',
    customer: {
      email,
      phone_number: '',
      name: '',
    },
    customizations: {
      title: '',
      description: '',
      logo: '',
    },
  } as FlutterWaveTypes.FlutterwaveConfig);

  const handleSubmit = (
    event: React.FormEvent<HTMLFormElement>,
    gateway: 'paystack' | 'flutterwave',
  ) => {
    event.preventDefault();

    if (gateway === 'paystack') {
      if (!isPaystackConfigured()) {
        announceError('Paystack not configured');
        return;
      }

      initializePaystackPayment({
        onSuccess: (response: unknown) => {
          onSuccess(response);
        },
        onClose: () => {
          announceError('Payment closed');
        },
        config: {
          email,
          amount: Math.round(amount * 100),
          currency,
          reference,
        },
      });
    } else if (gateway === 'flutterwave') {
      if (!isFlutterwaveConfigured()) {
        announceError('Flutterwave not configured');
        return;
      }

      initiateFlutterwavePayment({
        callback: (response: unknown) => {
          onSuccess(response);
        },
        onClose: () => {
          announceError('Payment closed');
        },
      });
    }
  };

  const paymentButtonClass = {
    default: 'w-full py-3 rounded-md font-medium transition-colors bg-indigo-600 text-white hover:bg-indigo-700',
    loading: 'w-full py-3 rounded-md font-medium text-sm bg-slate-300 text-slate-500 cursor-not-allowed disabled:pointer-events-none',
  };

  return (
    <form onSubmit={(e) => handleSubmit(e, gateway)} className="space-y-4">
      <div className="space-y-3">
        {gateway === 'paystack' ? (
          <button
            type="button"
            onClick={(e) => handleSubmit(e as unknown as React.FormEvent<HTMLFormElement>, 'paystack')}
            disabled={isLoading}
            className={isLoading ? paymentButtonClass.loading : paymentButtonClass.default}
          >
            {isLoading ? 'Processing...' : 'Pay with Paystack'}
          </button>
        ) : (
          <button
            type="button"
            onClick={(e) => handleSubmit(e as unknown as React.FormEvent<HTMLFormElement>, 'flutterwave')}
            disabled={isLoading}
            className={isLoading ? paymentButtonClass.loading : paymentButtonClass.default}
          >
            {isLoading ? 'Processing...' : 'Pay with Flutterwave'}
          </button>
        )}
      </div>

      <div aria-live="polite" aria-atomic="true" className="sr-only">
        {errorAnnouncement}
      </div>

      {error && (
        <p className="text-sm text-red-600" role="alert">
          {error}
        </p>
      )}
    </form>
  );
};

export default PaymentForm;