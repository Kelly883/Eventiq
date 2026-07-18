import { useEffect, useRef, useCallback } from 'react';
import { useAuthContext } from '../../auth/context/AuthContext';
import { requestNotificationPermissionAndToken } from '../../../config/firebase';
import { api } from '../../../lib/api';

const STORED_TOKEN_KEY = 'fcm_token';

/**
 * Keeps the backend's record of this device's FCM token in sync.
 *
 * The modern Firebase Web SDK (v9+) has no onTokenRefresh event the way
 * older native/legacy SDKs did - Firebase's own guidance is to call
 * getToken() periodically and diff against what you last registered.
 * This re-checks on: initial login, tab becoming visible again (covers
 * long-lived tabs where a token could rotate mid-session), and a
 * 24h interval as a fallback for tabs that are never backgrounded.
 *
 * When the token has changed, tells the backend about both the new and
 * previous token so it can delete the stale row instead of accumulating
 * dead device rows forever.
 */
export function useFCMTokenSync() {
  const { user } = useAuthContext() || {};
  const syncInFlight = useRef(false);

  const syncToken = useCallback(async () => {
    if (!user || syncInFlight.current) return;
    syncInFlight.current = true;

    try {
      const newToken = await requestNotificationPermissionAndToken();
      if (!newToken) return; // permission denied / unsupported / not configured

      const previousToken = localStorage.getItem(STORED_TOKEN_KEY);
      if (previousToken === newToken) return; // unchanged, nothing to do

      await api.post('/device-tokens', {
        fcm_token: newToken,
        previous_token: previousToken || undefined,
        platform: 'web',
      });

      localStorage.setItem(STORED_TOKEN_KEY, newToken);
    } catch (err) {
      console.warn('[firebase] Device token sync failed:', err);
    } finally {
      syncInFlight.current = false;
    }
  }, [user]);

  useEffect(() => {
    if (!user) return;

    syncToken();

    const onVisibilityChange = () => {
      if (document.visibilityState === 'visible') syncToken();
    };
    document.addEventListener('visibilitychange', onVisibilityChange);

    const interval = setInterval(syncToken, 24 * 60 * 60 * 1000); // 24h fallback

    return () => {
      document.removeEventListener('visibilitychange', onVisibilityChange);
      clearInterval(interval);
    };
  }, [user, syncToken]);
}
