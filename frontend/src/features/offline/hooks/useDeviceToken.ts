import { useState, useEffect, useCallback } from 'react';
import { forceNewDeviceToken, getDeviceToken, restoreDeviceToken } from '../services/deviceToken';

export function useDeviceToken() {
  const [token, setToken] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (typeof window === 'undefined') {
      setLoading(false);
      return;
    }
    let cancelled = false;
    (async () => {
      const restored = await restoreDeviceToken();
      if (cancelled) return;
      setToken(restored ?? getDeviceToken());
      setLoading(false);
    })();
    return () => {
      cancelled = true;
    };
  }, []);

  const regenerate = useCallback(() => {
    if (typeof window === 'undefined') return null;
    const newToken = forceNewDeviceToken();
    setToken(newToken);
    return newToken;
  }, []);

  return { token, loading, regenerate };
}
