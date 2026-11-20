import { useState, useEffect, useCallback } from 'react';

export interface CsrfTokenResult {
  token: string | null;
  setToken: (token: string) => void;
  refreshToken: () => Promise<string | null>;
  isLoading: boolean;
}

export function useCsrfToken(): CsrfTokenResult {
  const [token, setTokenState] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(false);

  const refreshToken = useCallback(async () => {
    setIsLoading(true);
    try {
      const response = await fetch('/sanctum/csrf-cookie', {
        method: 'GET',
        credentials: 'include',
      });
      if (response.ok) {
        const csrfToken = response.headers.get('X-XSRF-TOKEN');
        if (csrfToken) {
          setTokenState(csrfToken);
        }
      }
      return csrfToken || null;
    } catch (error) {
      console.error('Failed to refresh CSRF token:', error);
      return null;
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Initialize on mount
  useEffect(() => {
    refreshToken();
  }, [refreshToken]);

  const setToken = useCallback((csrfToken: string) => {
    setTokenState(csrfToken);
  }, []);

  return { token, setToken, refreshToken, isLoading };
}