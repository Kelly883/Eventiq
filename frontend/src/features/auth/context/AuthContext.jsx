import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { api } from '../../../lib/api';

const AuthContext = createContext(null);

const REMEMBER_ME_KEY = 'rememberMe';

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
          window.dispatchEvent(new Event('role-change'));
        }
        return fetchedUser;
      });
    } catch {
      setUser(null);
    }
  }, []);

  useEffect(() => {
    fetchCurrentUser().finally(() => setLoading(false));

    // Periodic session refresh (every 5 minutes) to detect role changes
    const interval = setInterval(async () => {
      await fetchCurrentUser();
    }, 300000);

    return () => clearInterval(interval);
  }, [fetchCurrentUser]);

  // Listen for role changes across tabs (BroadcastChannel API)
  useEffect(() => {
    const handleRoleChange = () => {
      fetchCurrentUser();
    };
    window.addEventListener('role-change', handleRoleChange);
    return () => window.removeEventListener('role-change', handleRoleChange);
  }, [fetchCurrentUser]);

  const login = useCallback(async (email, password, rememberMe = false) => {
    await api.get('/sanctum/csrf-cookie');
    const response = await api.post('/auth/login', { email, password, remember_me: rememberMe });

    localStorage.setItem(REMEMBER_ME_KEY, rememberMe ? 'true' : '');

    const res = await api.get('/auth/me');
    setUser(res.data);
    return { user: res.data, remember_me: response.data?.remember_me ?? false };
  }, []);

  const register = useCallback(async (email, password, name) => {
    await api.get('/sanctum/csrf-cookie');
    await api.post('/auth/register', { email, password, name });
    localStorage.removeItem(REMEMBER_ME_KEY);
    const res = await api.get('/auth/me');
    setUser(res.data);
    return res.data;
  }, []);

  const logout = useCallback(async () => {
    await api.post('/auth/logout');
    localStorage.removeItem(REMEMBER_ME_KEY);
    setUser(null);
  }, []);

  const forgotPassword = useCallback(async (email) => {
    await api.post('/auth/forgot-password', { email });
  }, []);

  const resetPassword = useCallback(async (token, newPassword) => {
    await api.get('/sanctum/csrf-cookie');
    await api.post('/auth/reset-password', { token, newPassword });
    localStorage.removeItem(REMEMBER_ME_KEY);
    setUser(null);
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
