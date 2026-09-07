import { useCallback } from 'react';
import { api } from '../../../lib/api';
import { forceNewDeviceToken, getDeviceToken } from '../services/deviceToken';

export function useRotateDeviceToken() {
  const rotate = useCallback(async () => {
    // No /api prefix: api baseURL already ends in /api.
    await api.post('/me/device-token/rotate', null, {
      headers: { 'X-Device-Token': getDeviceToken() },
    });
    // The server deleted the old token; mint a fresh identity (also clears
    // the in-memory cache so it cannot keep resurrecting the old one).
    return forceNewDeviceToken();
  }, []);

  return { rotate };
}
