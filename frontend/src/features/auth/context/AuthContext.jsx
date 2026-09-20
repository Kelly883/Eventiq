import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { api, refreshCsrf, showToast } from '../../../lib/api';
import { clearStoredDeviceToken } from '../../offline/services/deviceToken';

const AuthContext = createContext(null);

const REMEMBER_ME_KEY = 'rememberMe';
const SESSION_EVENT_KEY = 'eventiq-session-event';

// Unique id per tab, so messages broadcast by this tab are NOT re-treated as if
// they came from another tab. Guards against a self-inflicted hard-reload loop
// when an unauthenticated /auth/me 401 broadcasts 'session-ended'.
const TAB_ID = (() => {
  try {
    return typeof crypto !== 'undefined' && crypto.randomUUID
      ? crypto.randomUUID()
      : `tab-${Date.now()}-${Math.random().toString(36).slice(2)}`;
  } catch {
    return `tab-${Date.now()}-${Math.random().toString(36).slice(2)}`;
  }
})();

// Dispatches a same-document event that App.jsx (inside <Router>) listens for to
// perform SPA navigation, avoiding a full page reload / reload loop.
function emitRedirectToLogin({ source }) {
  if (typeof window === 'undefined') return;
  window.dispatchEvent(
    new CustomEvent('eventiq:redirect-login', { detail: { source: source || 'unknown' } })
  );
}

function broadcastAuthEvent(type) {
  const payload = JSON.stringify({ type, tabId: TAB_ID, ts: Date.now() });

  // BroadcastChannel API — works across tabs and windows.
  // NOTE: the originating tab's own listener (handleBroadcastMessage) ALSO
  // receives this message, so receivers guard on `tabId !== TAB_ID` for
  // actions that must only affect *other* tabs.
  if ('BroadcastChannel' in window) {
    try {
      const channel = new BroadcastChannel('auth-sync');
      channel.postMessage({ type, tabId: TAB_ID, ts: Date.now() });
      channel.close();
    } catch {
      // Fallback to storage event
    }
  }

  // Fallback / additional sync: localStorage storage event fires on other tabs
  // (not the tab that wrote it — that tab uses window.dispatchEvent instead)
  localStorage.setItem(SESSION_EVENT_KEY, payload);
  setTimeout(() => localStorage.removeItem(SESSION_EVENT_KEY), 1000);
}

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [sessionExpired, setSessionExpired] = useState(false);
  const [organizerId, setOrganizerId] = useState(null);

  const refreshAuth = useCallback(async () => {
    // NOTE: this is a background poll. It must NOT touch `loading` —
    // `loading` gates ProtectedRoute. Setting it true here would hide the
    // dashboard behind a spinner every 60s, and a transient failure would
    // setUser(null) → redirect to login.
    const hadTokenAtDispatch = Boolean(sessionStorage.getItem('auth_token'));
    try {
      const res = await api.get('/auth/me');
      setUser(res.data);
      setSessionExpired(false);
      const userId = res.data?.id;
      if (userId) {
        try {
          const meRes = await api.get(`/organizers/${userId}`);
          setOrganizerId(meRes.data?.data?.id || null);
        } catch {
          // An attendee or venue staff member may not have an organizer
          // profile; that must not invalidate their authenticated session.
          setOrganizerId(null);
        }
      }
    } catch (e) {
      const status = e?.response?.status;
      const hasTokenNow = Boolean(sessionStorage.getItem('auth_token'));
      if (status === 401 && (!hasTokenNow || hadTokenAtDispatch)) {
        // Only a confirmed 401 means the session is truly expired/invalid.
        // Guard against a stale race: a /auth/me dispatched BEFORE login
        // (no token) that resolves AFTER login stored the token is not a
        // session expiry — ignore it.
        setUser(null);
        setSessionExpired(true);
      }
      // Any other error (network, timeout, 5xx) is transient — keep the
      // user logged in. The next poll or the next focus/visibility check
      // will re-evaluate.
    }
  }, []);

  useEffect(() => {
    const interval = setInterval(refreshAuth, 60000);
    return () => clearInterval(interval);
  }, [refreshAuth]);

  const fetchCurrentUser = useCallback(async () => {
    const hasToken = Boolean(sessionStorage.getItem('auth_token'));
    try {
      const res = await api.get('/auth/me');
      const fetchedUser = res.data;
      setUser((prev) => {
        const prevRoles = prev?.roles?.map((r) => r.name).sort().join(',') || '';
        const newRoles = (fetchedUser?.roles || []).map((r) => r.name).sort().join(',') || '';
        if (prev && prevRoles !== newRoles) {
          // Dispatch within same document
          window.dispatchEvent(new Event('role-change'));
          // Broadcast role-change to all tabs
          if ('BroadcastChannel' in window) {
            broadcastAuthEvent('role-change');
          }
        }
        return fetchedUser;
      });
    } catch (err) {
      const status = err?.response?.status;
      const hasTokenNow = Boolean(sessionStorage.getItem('auth_token'));
      if (status === 401 && (!hasTokenNow || hasToken)) {
        // Confirmed expired/invalid — clear locally and sync across tabs.
        // Stale race guard: a /auth/me dispatched before login stored the
        // token must not clear the fresh session.
        setUser(null);
        broadcastAuthEvent('session-ended');
        throw err;
      }
      // Non-401 (network failure, timeout, 5xx): do NOT destroy auth state.
      // The user stays logged in; the next poll/focus check re-evaluates.
      throw err;
    }
  }, []);

  useEffect(() => {
    fetchCurrentUser()
      .catch((error) => {
        if (error?.response?.status !== 401) {
          console.error('[auth] Unable to restore the current session.', error);
        }
      })
      .finally(() => setLoading(false));

    let idleTimer;

    const scheduleRefresh = () => {
      clearTimeout(idleTimer);
      idleTimer = setTimeout(async () => {
        if (!document.hidden) {
          await fetchCurrentUser();
        }
      }, 300000);
    };

    // IDLE TIMEOUT: Check if user has been inactive for too long.
    // This is a UX convenience — the backend enforces the actual timeout.
    // We clear local state so the user doesn't see stale data.
    let idleLogoutTimer;
    const IDLE_TIMEOUT_MS = 30 * 60 * 1000; // 30 minutes - matches backend SESSION_IDLE_TIMEOUT_MINUTES

    const resetIdleTimer = () => {
      if (!user) return;
      clearTimeout(idleLogoutTimer);
      idleLogoutTimer = setTimeout(() => {
        if (!document.hidden) {
          showToast('Session expired', 'You were logged out due to inactivity.', 'warning');
          logout();
        }
      }, IDLE_TIMEOUT_MS);
    };

    // Track user activity to reset idle timer
    const handleUserActivity = () => {
      resetIdleTimer();
    };

    // Immediate session check when tab gains focus — catches expired sessions
    // from logout in another tab or server-side session expiry.
    // Guard: only fire the toast if we actually HAD a session. Otherwise a
    // guest landing on /login gets spammed on every tab switch.
    const handleFocus = async () => {
      if (!user) return;
      try {
        await fetchCurrentUser();
      } catch {
        window.dispatchEvent(new CustomEvent('session-expired'));
      }
    };

    const handleVisibility = async () => {
      if (!user || document.hidden) return;
      try {
        await fetchCurrentUser();
      } catch {
        window.dispatchEvent(new CustomEvent('session-expired'));
      }
    };

    document.addEventListener('visibilitychange', handleVisibility);
    window.addEventListener('focus', handleFocus);
    window.addEventListener('mousemove', scheduleRefresh);
    // Idle timeout: track user activity to reset the idle timer
    window.addEventListener('mousemove', handleUserActivity);
    window.addEventListener('keydown', handleUserActivity);
    window.addEventListener('click', handleUserActivity);
    window.addEventListener('scroll', handleUserActivity, true);
    window.addEventListener('touchstart', handleUserActivity);

    scheduleRefresh();
    resetIdleTimer();

    return () => {
      clearTimeout(idleTimer);
      clearTimeout(idleLogoutTimer);
      document.removeEventListener('visibilitychange', handleVisibility);
      window.removeEventListener('focus', handleFocus);
      window.removeEventListener('mousemove', scheduleRefresh);
      window.removeEventListener('mousemove', handleUserActivity);
      window.removeEventListener('keydown', handleUserActivity);
      window.removeEventListener('click', handleUserActivity);
      window.removeEventListener('scroll', handleUserActivity, true);
      window.removeEventListener('touchstart', handleUserActivity);
    };
  }, [fetchCurrentUser]);

  // Listen for session/role changes across tabs (full cross-tab sync)
  useEffect(() => {
    const handleBroadcastMessage = (event) => {
      const fromOtherTab = event?.data?.tabId !== TAB_ID;
      if (event?.data?.type === 'role-change') {
        fetchCurrentUser();
      } else if (event?.data?.type === 'session-ended') {
        // Only react to sessions ended in ANOTHER tab. The current tab's own
        // /auth/me 401 already handled local session clearing; self-broadcasts
        // must not trigger a redirect (prevents an infinite reload loop).
        if (!fromOtherTab) return;
        setUser(null);
        showToast('Session ended', 'Your session was terminated in another tab.', 'info');
        emitRedirectToLogin({ source: 'broadcast-session-ended' });
      } else if (event?.data?.type === 'session-established') {
        fetchCurrentUser();
      }
    };

    const handleStorageEvent = (e) => {
      if (e.key === SESSION_EVENT_KEY && e.newValue) {
        try {
          const eventData = JSON.parse(e.newValue);
          // storage events never fire in the tab that wrote them, so this is
          // always from another tab.
          if (eventData.type === 'role-change') {
            fetchCurrentUser();
          } else if (eventData.type === 'session-ended') {
            setUser(null);
            showToast('Session ended', 'Your session was terminated in another tab.', 'info');
            emitRedirectToLogin({ source: 'storage-session-ended' });
          } else if (eventData.type === 'session-established') {
            fetchCurrentUser();
          }
        } catch {
          // ignore parse errors
        }
      } else if (e.key === 'eventiqDeviceToken' && e.newValue) {
        // Sync device token across tabs when it changes in another tab.
        try {
          const newToken = e.newValue;
          if (typeof window !== 'undefined' && window.EventiqDevice?.getDeviceToken) {
            const currentToken = window.EventiqDevice.getDeviceToken();
            if (currentToken !== newToken) {
              localStorage.setItem('eventiqDeviceToken', newToken);
            }
          }
        } catch {
          // ignore token sync failures
        }
      }
    };

    const handleRoleChange = () => {
        fetchCurrentUser();
      };

      const handleSessionExpired = () => {
        // Fired by api.ts when a 401 refresh fails — shows modal.
        setUser(null);
        window.dispatchEvent(new CustomEvent('session-expired'));
        emitRedirectToLogin({ source: 'session-expired' });
      };

      window.addEventListener('role-change', handleRoleChange);
      window.addEventListener('session-expired', handleSessionExpired);
        window.addEventListener('storage', handleStorageEvent);

    if ('BroadcastChannel' in window) {
      const channel = new BroadcastChannel('auth-sync');
      channel.addEventListener('message', handleBroadcastMessage);
      return () => {
        channel.close();
        window.removeEventListener('storage', handleStorageEvent);
        window.removeEventListener('role-change', handleRoleChange);
        window.removeEventListener('session-expired', handleSessionExpired);
      };
    }

    return () => {
      window.removeEventListener('storage', handleStorageEvent);
      window.removeEventListener('role-change', handleRoleChange);
        window.removeEventListener('session-expired', handleSessionExpired);
    };
  }, [fetchCurrentUser]);

  const login = useCallback(async (email, password, rememberMe = false, captchaToken = null) => {
    await refreshCsrf();
    const payload = { email, password, remember_me: rememberMe };
    if (captchaToken) payload.captcha_token = captchaToken;
    const response = await api.post('/auth/login', payload);

    localStorage.setItem(REMEMBER_ME_KEY, rememberMe ? 'true' : '');

    const user = response.data?.user;
    const token = response.data?.token;
    if (user && token) {
      // Store the Bearer token so api.ts can attach it to protected requests.
      // The token is a 64-char random string issued by POST /api/auth/login.
      // It is NOT a Sanctum PAT — it maps to a row in the `sessions` table
      // validated by BearerTokenAuth middleware. We keep it in memory only
      // (not localStorage) so it is cleared when the tab closes.
      sessionStorage.setItem('auth_token', token);
      setUser(user);
      setSessionExpired(false);
      broadcastAuthEvent('session-established');
      return { user, remember_me: response.data?.remember_me ?? false, token };
    }

    // Fallback: token missing (should not happen with current backend contract)
    // — fall back to /auth/me which may still work if backend sets a session cookie.
    const res = await api.get('/auth/me');
    setUser(res.data);
    setSessionExpired(false);
    broadcastAuthEvent('session-established');
    return { user: res.data, remember_me: response.data?.remember_me ?? false };
  }, []);

  const register = useCallback(async (email, password, name, passwordConfirmation = null) => {
    await refreshCsrf();
    const res = await api.post('/auth/register', {
      email,
      password,
      password_confirmation: passwordConfirmation ?? password,
      name,
    });
    // Register now returns generic 200 to avoid email enumeration (same
    // response whether email existed or was created). Do not auto-login;
    // caller should redirect to /login with the generic message.
    localStorage.removeItem(REMEMBER_ME_KEY);
    return res.data;
  }, []);

  const logout = useCallback(async () => {
    try {
      const deviceToken = typeof window !== 'undefined' && window.EventiqDevice?.getDeviceToken
        ? window.EventiqDevice.getDeviceToken()
        : null;
      if (deviceToken) {
        await api.patch(`/notifications/device-tokens/${deviceToken}/offline-status`, {
          offline_enabled: false,
        });
      }
    } catch {
      // logout must not fail because device-token cleanup did
    }

    try {
      await clearStoredDeviceToken();
    } catch {
      // ignore local cleanup failures
    }

    await api.post('/auth/logout');
    localStorage.removeItem(REMEMBER_ME_KEY);
    sessionStorage.removeItem('auth_token');
    setUser(null);
    setSessionExpired(false);
    setOrganizerId(null);
    broadcastAuthEvent('session-ended');
  }, []);

  const forgotPassword = useCallback(async (email) => {
    await api.post('/auth/forgot-password', { email });
  }, []);

  const resetPassword = useCallback(async (token, email, password, passwordConfirmation = null) => {
    await refreshCsrf();
    await api.post('/auth/reset-password', {
      token,
      email,
      password,
      password_confirmation: passwordConfirmation ?? password,
    });
    localStorage.removeItem(REMEMBER_ME_KEY);
    setUser(null);
    // Broadcast session invalidation to all tabs
    broadcastAuthEvent('session-ended');
  }, []);

  const checkAdminAccess = useCallback(() => {
    return Boolean(user?.roles?.some((role) => role.name === 'admin'));
  }, [user]);

  return (
    <AuthContext.Provider value={{ user, isAuthenticated: Boolean(user) && !sessionExpired, loading, checkAdminAccess, login, register, logout, forgotPassword, resetPassword, refreshAuth, sessionExpired, setSessionExpired, organizerId }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuthContext = () => {
  return useContext(AuthContext);
};
