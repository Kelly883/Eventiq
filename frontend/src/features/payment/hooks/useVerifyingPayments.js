import { useState } from 'react';

export function useVerifyingPayments() {
  const [loading, setLoading] = useState(false);

  const verify = async ({ gateway, reference }) => {
    setLoading(true);
    try {
      const res = await fetch(`${import.meta.env.VITE_API_BASE_URL || ''}/api/payments/${gateway}/verify`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          gateway,
          reference,
        }),
      });

      if (!res.ok) {
        const text = await res.text();
        throw new Error(text || `Verify failed (${res.status})`);
      }

      const json = await res.json();
      return json.data;
    } finally {
      setLoading(false);
    }
  };

  return { verify, loading };
}


