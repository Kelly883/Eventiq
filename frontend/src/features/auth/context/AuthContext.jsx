import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { api } from '../../../lib/api';

const AuthContext = createContext(null);

const AUTH_TOKEN_STORAGE_KEY = 'authToken';

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(() => localStorage.getItem(AUTH_TOKEN_STORAGE_KEY));
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const storedToken = localStorage.getItem(AUTH_TOKEN_STORAGE_KEY);
    if (!storedToken) {
      setLoading(false);
      return;
    }

    api.get('/auth/me')
      .then((res) => {
        setUser(res.data);
        setToken(storedToken);
      })
      .catch(() => {
        localStorage.removeItem(AUTH_TOKEN_STORAGE_KEY);
        setToken(null);
        setUser(null);
      })
      .finally(() => setLoading(false));
  }, []);

  const login = useCallback(async (email, password) => {
    const response = await api.post('/auth/login', { email, password });
    const { token, user } = response.data;
    localStorage.setItem(AUTH_TOKEN_STORAGE_KEY, token);
    setToken(token);
    setUser(user);
    return user;
  }, []);

  const register = useCallback(async (email, password, name) => {
    const response = await api.post('/auth/register', { email, password, name });
    const { token, user } = response.data;
    localStorage.setItem(AUTH_TOKEN_STORAGE_KEY, token);
    setToken(token);
    setUser(user);
    return user;
  }, []);

  const logout = useCallback(() => {
    localStorage.removeItem(AUTH_TOKEN_STORAGE_KEY);
    setToken(null);
    setUser(null);
  }, []);

  const forgotPassword = useCallback(async (email) => {
    await api.post('/auth/forgot-password', { email });
  }, []);

  const resetPassword = useCallback(async (token, newPassword) => {
    await api.post('/auth/reset-password', { token, newPassword });
    localStorage.removeItem(AUTH_TOKEN_STORAGE_KEY);
    setToken(null);
    setUser(null);
  }, []);

  const checkAdminAccess = useCallback(() => {
    return Boolean(user?.roles?.some((role) => role.name === 'admin'));
  }, [user]);

  return (
    <AuthContext.Provider value={{ user, token, isAuthenticated: Boolean(user), loading, checkAdminAccess, login, register, logout, forgotPassword, resetPassword }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuthContext = () => {
  return useContext(AuthContext);
};
