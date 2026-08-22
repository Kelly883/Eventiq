import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { api } from '../../../lib/api';

const AuthContext = createContext(null);

const REMEMBER_ME_KEY = 'rememberMe';

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

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

  const login = useCallback(async (email, password, rememberMe = false) => {
    await api.get('/sanctum/csrf-cookie');
    await api.post('/auth/login', { email, password });

    if (rememberMe) {
      localStorage.setItem(REMEMBER_ME_KEY, 'true');
    } else {
      localStorage.removeItem(REMEMBER_ME_KEY);
    }

    const res = await api.get('/auth/me');
    setUser(res.data);
    return res.data;
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
