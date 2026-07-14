/**
 * Flutterwave public config, read from Vite env vars.
 * Only the PUBLIC key belongs here — never the secret/encryption keys.
 */
export const flutterwaveConfig = {
  publicKey: import.meta.env.VITE_FLUTTERWAVE_PUBLIC_KEY || '',
  currency: 'NGN',
};

export function isFlutterwaveConfigured() {
  return Boolean(flutterwaveConfig.publicKey);
}
