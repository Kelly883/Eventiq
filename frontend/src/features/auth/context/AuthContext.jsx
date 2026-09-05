import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { api, refreshCsrf, showToast } from '../../../lib/api';

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
    setLoading(true);
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
      setUser(null);
      setSessionExpired(true);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    const interval = setInterval(refreshAuth, 60000);
    return () => clearInterval(interval);
  }, [refreshAuth]);

  const fetchCurrentUser = useCallback(async () => {
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
      // If /auth/me fails with 401, session is expired — clear locally and sync
      const status = err?.response?.status;
      if (status === 401) {
        setUser(null);
        broadcastAuthEvent('session-ended');
        throw err;
      } else {
        setUser(null);
        throw err;
      }
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

    // Immediate session check when tab gains focus — catches expired sessions
    // from logout in another tab or server-side session expiry.
    // Guard: only fire the toast if we actually HAD a session. Otherwise a
    // guest landing on /login gets spammed on every tab switch.
    const handleFocus = async () => {
      if (!user) return;
      try {
        await fetchCurrentUser();
      } catch {
        showToast('Session expired', 'Please log in again to continue.', 'warning');
      }
    };

    const handleVisibility = async () => {
      if (!user || document.hidden) return;
      try {
        await fetchCurrentUser();
      } catch {
        showToast('Session expired', 'Please log in again to continue.', 'warning');
      }
    };

    document.addEventListener('visibilitychange', handleVisibility);
    window.addEventListener('focus', handleFocus);
    window.addEventListener('mousemove', scheduleRefresh);

    scheduleRefresh();

    return () => {
      clearTimeout(idleTimer);
      document.removeEventListener('visibilitychange', handleVisibility);
      window.removeEventListener('focus', handleFocus);
      window.removeEventListener('mousemove', scheduleRefresh);
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
      }
    };

    const handleRoleChange = () => {
        fetchCurrentUser();
      };

      const handleSessionExpired = () => {
        // Fired by api.ts when a 401 refresh fails — redirects to /login.
        // Use SPA navigation (via the router) rather than a hard reload so we
        // never cascade into a reload loop for unauthenticated users.
        setUser(null);
        showToast('Session expired', 'Your session has ended. Please log in again.', 'warning');
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

  const login = useCallback(async (email, password, rememberMe = false) => {
    await refreshCsrf();
    const response = await api.post('/auth/login', { email, password, remember_me: rememberMe });

    localStorage.setItem(REMEMBER_ME_KEY, rememberMe ? 'true' : '');

    const user = response.data?.user;
    if (user) {
      setUser(user);
      setSessionExpired(false);
      broadcastAuthEvent('session-established');
      return { user, remember_me: response.data?.remember_me ?? false };
    }

    const res = await api.get('/auth/me');
    setUser(res.data);
    setSessionExpired(false);
    broadcastAuthEvent('session-established');
    return { user: res.data, remember_me: response.data?.remember_me ?? false };
  }, []);

  const register = useCallback(async (email, password, name, passwordConfirmation = null) => {
    await api.get('/sanctum/csrf-cookie');
    await api.post('/auth/register', {
      email,
      password,
      password_confirmation: passwordConfirmation ?? password,
      name,
    });
    localStorage.removeItem(REMEMBER_ME_KEY);
    const res = await api.get('/auth/me');
    setUser(res.data);
    setSessionExpired(false);
    broadcastAuthEvent('session-established');
    return res.data;
  }, []);

  const logout = useCallback(async () => {
    await api.post('/auth/logout');
    localStorage.removeItem(REMEMBER_ME_KEY);
    setUser(null);
    setOrganizerId(null);
    // Broadcast session invalidation to all tabs
    broadcastAuthEvent('session-ended');
  }, []);

  const forgotPassword = useCallback(async (email) => {
    await api.post('/auth/forgot-password', { email });
  }, []);

  const resetPassword = useCallback(async (token, email, newPassword, passwordConfirmation) => {
    await api.get('/sanctum/csrf-cookie');
    await api.post('/auth/reset-password', { token, email, newPassword, password_confirmation: passwordConfirmation });
    localStorage.removeItem(REMEMBER_ME_KEY);
    setUser(null);
    // Broadcast session invalidation to all tabs
    broadcastAuthEvent('session-ended');
  }, []);

  const checkAdminAccess = useCallback(() => {
    return Boolean(user?.roles?.some((role) => role.name === 'admin'));
  }, [user]);

  return (
    <AuthContext.Provider value={{ user, isAuthenticated: Boolean(user) && !sessionExpired, loading, checkAdminAccess, login, register, logout, forgotPassword, resetPassword, refreshAuth, sessionExpired, organizerId }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuthContext = () => {
  return useContext(AuthContext);
};
