import { useState, useEffect, useCallback } from 'react';
import { getDeviceToken, getDeviceTokenStorageKey } from '../services/deviceToken';

export function useDeviceToken() {
  const [token, setToken] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (typeof window === 'undefined') {
      setLoading(false);
      return;
    }
    const existing = getDeviceToken();
    setToken(existing);
    setLoading(false);
  }, []);

  const regenerate = useCallback(() => {
    if (typeof window === 'undefined') return null;
    localStorage.removeItem(getDeviceTokenStorageKey());
    const newToken = getDeviceToken();
    setToken(newToken);
    return newToken;
  }, []);

  return { token, loading, regenerate };
}
