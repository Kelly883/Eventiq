import { useState } from 'react';

export function useInitializingPayments() {
  const [loading, setLoading] = useState(false);

  const initialize = async ({ gateway, amount, currency, email, reference, callbackUrl, metadata }) => {
    setLoading(true);
    try {
      const rawBase = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/+$/, '');
      const normalizedBase = rawBase.endsWith('/api') ? rawBase : rawBase ? `${rawBase}/api` : '/api';
      const res = await fetch(`${normalizedBase}/payments/${gateway}/initialize`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          // backend expects these fields per FormRequest scaffolding
          gateway,
          amount,
          currency: currency || 'NGN',
          email,
          reference,
          callback_url: callbackUrl,
          metadata,
          name: metadata?.name,
          phone: metadata?.phone,
          customizations: metadata?.customizations,
        }),
      });

      if (!res.ok) {
        const text = await res.text();
        throw new Error(text || `Initialize failed (${res.status})`);
      }

      const json = await res.json();
      return json.data;
    } finally {
      setLoading(false);
    }
  };

  return { initialize, loading };
}


