import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { api, showToast } from '../../../lib/api';

const AuthContext = createContext(null);

const REMEMBER_ME_KEY = 'rememberMe';
const SESSION_EVENT_KEY = 'eventiq-session-event';

function broadcastAuthEvent(type) {
  const payload = JSON.stringify({ type, ts: Date.now() });

  // BroadcastChannel API — works across tabs and windows
  if ('BroadcastChannel' in window) {
    try {
      const channel = new BroadcastChannel('auth-sync');
      channel.postMessage({ type });
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
          if (BroadcastChannel) {
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
    fetchCurrentUser().finally(() => setLoading(false));

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
    // from logout in another tab or server-side session expiry
    const handleFocus = async () => {
      try {
        await fetchCurrentUser();
      } catch {
        showToast('Session expired', 'Please log in again to continue.', 'warning');
      }
    };

    const handleVisibility = async () => {
      if (!document.hidden) {
        try {
          await fetchCurrentUser();
        } catch {
          showToast('Session expired', 'Please log in again to continue.', 'warning');
        }
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
      if (event?.data?.type === 'role-change') {
        fetchCurrentUser();
      } else if (event?.data?.type === 'session-ended') {
        setUser(null);
        showToast('Session ended', 'Your session was terminated in another tab.', 'info');
        window.location.href = '/login';
      } else if (event?.data?.type === 'session-established') {
        fetchCurrentUser();
      }
    };

    const handleStorageEvent = (e) => {
      if (e.key === SESSION_EVENT_KEY && e.newValue) {
        try {
          const eventData = JSON.parse(e.newValue);
          if (eventData.type === 'role-change') {
            fetchCurrentUser();
          } else if (eventData.type === 'session-ended') {
            setUser(null);
            showToast('Session ended', 'Your session was terminated in another tab.', 'info');
            window.location.href = '/login';
          } else if (eventData.type === 'session-established') {
            fetchCurrentUser();
          }
        } catch {
          // ignore parse errors
        }
      }
    };

    window.addEventListener('role-change', () => fetchCurrentUser());
    window.addEventListener('storage', handleStorageEvent);

    if ('BroadcastChannel' in window) {
      const channel = new BroadcastChannel('auth-sync');
      channel.addEventListener('message', handleBroadcastMessage);
      return () => {
        channel.close();
        window.removeEventListener('storage', handleStorageEvent);
        window.removeEventListener('role-change', () => fetchCurrentUser());
      };
    }

    return () => {
      window.removeEventListener('storage', handleStorageEvent);
      window.removeEventListener('role-change', () => fetchCurrentUser());
    };
  }, [fetchCurrentUser]);

  const login = useCallback(async (email, password, rememberMe = false) => {
    await api.get('/sanctum/csrf-cookie');
    const response = await api.post('/auth/login', { email, password, remember_me: rememberMe });

    localStorage.setItem(REMEMBER_ME_KEY, rememberMe ? 'true' : '');

    const res = await api.get('/auth/me');
    setUser(res.data);
    // Broadcast session change to all tabs
    broadcastAuthEvent('session-established');
    return { user: res.data, remember_me: response.data?.remember_me ?? false };
  }, []);

  const register = useCallback(async (email, password, name) => {
    await api.get('/sanctum/csrf-cookie');
    await api.post('/auth/register', { email, password, name });
    localStorage.removeItem(REMEMBER_ME_KEY);
    const res = await api.get('/auth/me');
    setUser(res.data);
    broadcastAuthEvent('session-established');
    return res.data;
  }, []);

  const logout = useCallback(async () => {
    await api.post('/auth/logout');
    localStorage.removeItem(REMEMBER_ME_KEY);
    setUser(null);
    // Broadcast session invalidation to all tabs
    broadcastAuthEvent('session-ended');
  }, []);

  const forgotPassword = useCallback(async (email) => {
    await api.post('/auth/forgot-password', { email });
  }, []);

  const resetPassword = useCallback(async (token, newPassword) => {
    await api.get('/sanctum/csrf-cookie');
    await api.post('/auth/reset-password', { token, newPassword });
    localStorage.removeItem(REMEMBER_ME_KEY);
    setUser(null);
    // Broadcast session invalidation to all tabs
    broadcastAuthEvent('session-ended');
  }, []);

  const checkAdminAccess = useCallback(() => {
    return Boolean(user?.roles?.some((role) => role.name === 'admin'));
  }, [user]);

  return (
    <AuthContext.Provider value={{ user, isAuthenticated: Boolean(user), loading, checkAdminAccess, login, register, logout, forgotPassword, resetPassword }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuthContext = () => {
  return useContext(AuthContext);
};
