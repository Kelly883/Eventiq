import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { api } from '../../../lib/api';

const AuthContext = createContext(null;

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
        const meRes = await api.get(`/organizers/user/${userId}`);
        setOrganizerId(meRes.data?.data?.id || null);
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

  useEffect(() => {
    api.get('/auth/me')
      .then((res) => {
        setUser(res.data);
      })
      .catch(() => {
        setUser(null);
      })
      .finally(() => setLoading(false));
  }, []);

  const login = useCallback(async (email, password) => {
    await api.get('/sanctum/csrf-cookie');
    await api.post('/auth/login', { email, password });
    const res = await api.get('/auth/me');
    setUser(res.data);
    setSessionExpired(false);
    return res.data;
  }, []);

  const register = useCallback(async (email, password, name) => {
    await api.get('/sanctum/csrf-cookie');
    await api.post('/auth/register', { email, password, name });
    const res = await api.get('/auth/me');
    setUser(res.data);
    setSessionExpired(false);
    return res.data;
  }, []);

  const logout = useCallback(async () => {
    await api.post('/auth/logout');
    setUser(null);
    setOrganizerId(null);
  }, []);

  const forgotPassword = useCallback(async (email) => {
    await api.post('/auth/forgot-password', { email });
  }, []);

  const resetPassword = useCallback(async (token, newPassword) => {
    await api.get('/sanctum/csrf-cookie');
    await api.post('/auth/reset-password', { token, newPassword });
    setUser(null);
  }, []);

  const checkAdminAccess = useCallback(() => {
    return Boolean(user?.roles?.some((role) => role.name === 'admin'));
  }, [user, user?.roles]);

  return (
    <AuthContext.Provider value={{ user, isAuthenticated: Boolean(user) && !sessionExpired, loading, checkAdminAccess, login, register, logout, forgotPassword, resetPassword, refreshAuth, sessionExpired, organizerId }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuthContext = () => {
  return useContext(AuthContext);
};

export const useAuthContext = () => {
  return useContext(AuthContext);
};
