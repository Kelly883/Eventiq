/**
 * Paystack public config, read from Vite env vars.
 * Only the PUBLIC key belongs here — never the secret key.
 */
export const paystackConfig = {
  publicKey: import.meta.env.VITE_PAYSTACK_PUBLIC_KEY || '',
  currency: 'NGN',
};

export function isPaystackConfigured() {
  return Boolean(paystackConfig.publicKey);
}
