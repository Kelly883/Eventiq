import React, { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { api } from '../../../lib/api';

const AuthContext = createContext(null);

const AUTH_TOKEN_STORAGE_KEY = 'authToken';

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  // Hydrate the session on load: if a token already exists (returning
  // user), fetch the current user rather than starting every page load
  // logged-out until the next explicit login.
  useEffect(() => {
    const token = localStorage.getItem(AUTH_TOKEN_STORAGE_KEY);
    if (!token) {
      setLoading(false);
      return;
    }

    api.get('/auth/me')
      .then((res) => setUser(res.data))
      .catch(() => {
        // Token is invalid/expired - lib/api.ts's own 401 interceptor
        // already handles refresh/logout for ongoing requests; this
        // just makes sure we don't render as "logged in" on a stale token.
        setUser(null);
      })
      .finally(() => setLoading(false));
  }, []);

  const checkAdminAccess = useCallback(() => {
    return Boolean(user?.roles?.some((role) => role.name === 'admin'));
  }, [user]);

  return (
    <AuthContext.Provider value={{ user, setUser, loading, checkAdminAccess }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuthContext = () => {
  return useContext(AuthContext);
};
