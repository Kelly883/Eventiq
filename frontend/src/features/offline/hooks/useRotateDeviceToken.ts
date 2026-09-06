import { useCallback } from 'react';
import { api } from '../../../lib/api';
import { getDeviceToken, getDeviceTokenStorageKey } from '../services/deviceToken';

export function useRotateDeviceToken() {
  const rotate = useCallback(async () => {
    await api.post('/api/me/device-token/rotate');
    localStorage.removeItem(getDeviceTokenStorageKey());
    const newToken = getDeviceToken();
    return newToken;
  }, []);

  return { rotate };
}
