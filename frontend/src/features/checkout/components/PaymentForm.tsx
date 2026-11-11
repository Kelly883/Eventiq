import React, { useCallback, useState } from 'react';
import { useForm } from 'react-hook-form';
import { z } from 'zod';
import { zodResolver } from '@hookform/resolvers/zod';
import { usePaystackPayment } from 'react-paystack';
import type { InitializePayment } from 'react-paystack/dist/types';
import { paystackConfig, isPaystackConfigured } from '../../payment/utils/paystackConfig';
import { useFlutterwave, type FlutterWaveTypes } from 'flutterwave-react-v3';
import { flutterwaveConfig, isFlutterwaveConfigured } from '../../payment/utils/flutterwaveConfig';

export type PaymentGateway = 'paystack' | 'flutterwave';

const paymentSchema = z.object({
  email: z.string().email('Enter a valid email address').min(1, 'Email is required'),
  gateway: z.enum(['paystack', 'flutterwave']),
});

interface PaymentFormProps {
  clientSecret?: string;
  onSuccess: (response: any) => void;
  onError: (error: string) => void;
  isLoading: boolean;
  gateway?: PaymentGateway;
  amount: number;
  currency: string;
  email?: string;
  reference?: string;
}

interface PaymentInitiator {
  initiate: (data: { email: string; amount: number; currency: string; reference: string }) => void;
  isConfigured: () => boolean;
  label: string;
}

function buildPaymentInitiators(
  amount: number,
  currency: string,
  reference: string,
  onSuccess: (response: any) => void,
  announceError: (message: string) => void,
): Record<PaymentGateway, PaymentInitiator> {
  const initializePaystackPayment: InitializePayment = usePaystackPayment({
    publicKey: paystackConfig.publicKey,
    amount: Math.round(amount * 100),
    currency: currency as 'NGN' | 'USD' | 'GHS' | 'ZAR' | 'KES',
    reference,
  });

  const initiateFlutterwavePayment = useFlutterwave({
    public_key: flutterwaveConfig.publicKey,
    tx_ref: reference,
    amount,
    currency,
    payment_options: 'card',
    customer: { email: '', phone_number: '', name: '' },
    customizations: { title: '', description: '', logo: '' },
  } as FlutterWaveTypes.FlutterwaveConfig);

  return {
    paystack: {
      label: 'Pay with Paystack',
      isConfigured: isPaystackConfigured,
      initiate: ({ email }) => {
        if (!isPaystackConfigured()) {
          announceError('Paystack is not configured.');
          return;
        }
        initializePaystackPayment({
          onSuccess: (response: unknown) => onSuccess(response),
          onClose: () => announceError('Payment was closed.'),
          config: { email, amount: Math.round(amount * 100), currency, reference },
        });
      },
    },
    flutterwave: {
      label: 'Pay with Flutterwave',
      isConfigured: isFlutterwaveConfigured,
      initiate: ({ email }) => {
        if (!isFlutterwaveConfigured()) {
          announceError('Flutterwave is not configured.');
          return;
        }
        initiateFlutterwavePayment({
          callback: (response: unknown) => onSuccess(response),
          onClose: () => announceError('Payment was closed.'),
        });
      },
    },
  };
}

const PaymentForm: React.FC<PaymentFormProps> = ({
  clientSecret,
  onSuccess,
  onError,
  isLoading,
  gateway: defaultGateway = 'paystack',
  amount,
  currency = 'NGN',
  email: defaultEmail,
  reference = 'eventiq-checkout',
}) => {
  const [formError, setFormError] = useState<string | null>(null);
  const [errorAnnouncement, setErrorAnnouncement] = useState<string | null>(null);

  const announceError = useCallback((message: string) => {
    setFormError(message);
    setErrorAnnouncement(message);
    onError(message);
    setTimeout(() => setErrorAnnouncement(null), 1000);
  }, [onError]);

  const initiators = buildPaymentInitiators(
    amount,
    currency,
    reference,
    onSuccess,
    announceError,
  );

  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<{ email: string; gateway: PaymentGateway }>({
    resolver: zodResolver(paymentSchema),
    defaultValues: {
      email: defaultEmail ?? '',
      gateway: defaultGateway,
    },
  });

  const onFormSubmit = useCallback(
    (data: { email: string; gateway: PaymentGateway }) => {
      setFormError(null);
      const initiator = initiators[data.gateway];
      if (!initiator.isConfigured()) {
        announceError(`${initiator.label} is not configured.`);
        return;
      }
      initiator.initiate({
        email: data.email,
        amount,
        currency,
        reference,
      });
    },
    [initiators, amount, currency, reference, announceError],
  );

  const selectedGateway = defaultGateway;
  const initiator = initiators[selectedGateway];

  const buttonClass = {
    default:
      'w-full py-3 rounded-md font-medium transition-colors bg-indigo-600 text-white hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500',
    loading:
      'w-full py-3 rounded-md font-medium text-sm bg-slate-300 text-slate-500 cursor-not-allowed disabled:pointer-events-none',
  };

  return (
    <form
      onSubmit={handleSubmit(onFormSubmit)}
      className="space-y-4"
      noValidate
    >
      <div className="space-y-3">
        <label
          htmlFor="payment-email"
          className="block text-sm font-medium text-slate-700"
        >
          Email address
        </label>
        <input
          id="payment-email"
          {...register('email')}
          type="email"
          autoComplete="email"
          required
          className={`w-full px-3 py-2 rounded-md border text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 ${
            errors.email ? 'border-red-500 focus:ring-red-500' : 'border-slate-300'
          }`}
          placeholder="you@example.com"
          aria-invalid={errors.email ? 'true' : 'false'}
          aria-describedBy={errors.email ? 'email-error' : undefined}
          disabled={isLoading}
        />
        {errors.email && (
          <p id="email-error" className="text-sm text-red-600" role="alert">
            {errors.email.message}
          </p>
        )}
      </div>

      <div className="space-y-3">
        <label
          htmlFor="payment-gateway"
          className="block text-sm font-medium text-slate-700"
        >
          Payment method
        </label>
        <div
          id="payment-gateway"
          role="radiogroup"
          aria-label="Payment gateway"
          className="flex gap-3"
        >
          {(['paystack', 'flutterwave'] as PaymentGateway[]).map((gw) => {
            const inc = initiators[gw];
            const checked = selectedGateway === gw;
            return (
              <label
                key={gw}
                className="flex items-center gap-2 cursor-pointer"
              >
                <input
                  type="radio"
                  name="gateway"
                  value={gw}
                  checked={checked}
                  className="rounded text-indigo-600 focus:ring-indigo-500"
                  disabled={isLoading || !inc.isConfigured()}
                  aria-label={inc.label}
                  aria-disabled={isLoading || !inc.isConfigured()}
                />
                <span className="text-sm text-slate-700">
                  {inc.label}{!inc.isConfigured() ? ' (not configured)' : ''}
                </span>
              </label>
            );
          })}
        </div>
      </div>

      <button
        type="submit"
        disabled={isLoading || !initiator?.isConfigured()}
        className={isLoading ? buttonClass.loading : buttonClass.default}
      >
        {isLoading ? 'Processing…' : initiator ? initiator.label : 'Pay'}
      </button>

      <div
        aria-live="polite"
        aria-atomic="true"
        className="sr-only"
      >
        {errorAnnouncement}
      </div>

      {formError && (
        <p className="text-sm text-red-600" role="alert">
          {formError}
        </p>
      )}
    </form>
  );
};

export default PaymentForm;

