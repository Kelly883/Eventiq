import React, { useState, useEffect } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { useAuthContext } from '../context/AuthContext';
import { showToast } from '../../../lib/api';

const LoginPage = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const navigate = useNavigate();
  const location = useLocation();
  const { login, user } = useAuthContext();

  const sessionExpiredReturn = (() => {
    try {
      return sessionStorage.getItem('session-expired-return');
    } catch {
      return null;
    }
  })();

  useEffect(() => {
    if (location.state?.message && location.state?.from) {
      const fromPath = location.state.from.pathname || '';
      const isProtectedRoute = fromPath.startsWith('/admin/') ||
        fromPath.startsWith('/dashboard/') ||
        fromPath.startsWith('/organizer/');
      if (isProtectedRoute) {
        showToast('Authentication Required', 'Please log in to access the protected route.', 'info');
      } else if (location.state.message) {
        showToast('Notice', location.state.message, location.state.messageType || 'warning');
      }
    }

    if (sessionExpiredReturn) {
      showToast('Session expired', 'Please log in again to continue.', 'warning');
    }
  }, [location.state?.message, location.state?.messageType, location.state?.from, sessionExpiredReturn]);

  useEffect(() => {
    if (sessionExpiredReturn) {
      try { sessionStorage.removeItem('session-expired-return'); } catch { /* ignore */ }
    }
  }, [sessionExpiredReturn]);

  const getDefaultRedirect = () => {
    const userRoles = user?.roles?.map((r) => r.name) || [];
    if (userRoles.includes('organizer')) return '/dashboard/organizer';
    return '/dashboard';
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      await login(email, password);
      showToast('Session Extended', 'Your session will remain active.', 'info');
      // Compute redirect after login when user roles are available
      const redirectTo = location.state?.from?.pathname || sessionExpiredReturn || getDefaultRedirect();
      navigate(redirectTo, { replace: true });
    } catch (err) {
      if (err.response?.status === 419) {
        setError('Security token expired. Please refresh the page and try again.');
      } else {
        setError(
          err.response?.data?.message ||
          err.message ||
          'Invalid email or password.'
        );
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-50 p-4">
      <div className="max-w-md w-full bg-white p-8 rounded-xl border border-slate-200 shadow-sm">
        <h1 className="text-2xl font-bold text-slate-900 mb-2">Welcome Back</h1>
        <p className="text-sm text-slate-500 mb-6">Log in to your Eventiq account.</p>

        {error && (
          <div className="mb-4 p-3 bg-red-50 text-red-700 rounded-lg text-sm">
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">
              Email Address
            </label>
            <input
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              required
              autoComplete="email"
              placeholder="you@example.com"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">
              Password
            </label>
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              required
              autoComplete="current-password"
              placeholder="••••••••"
            />
          </div>

          <button
            type="submit"
            disabled={loading}
            className="w-full py-2.5 px-4 rounded-lg bg-indigo-600 text-white font-bold hover:bg-indigo-700 disabled:opacity-50 transition-colors"
          >
            {loading ? 'Signing in...' : 'Sign In'}
          </button>
        </form>

        <div className="mt-4 flex items-center justify-between text-sm text-slate-600">
          <button
            type="button"
            onClick={() => navigate('/forgot-password')}
            className="text-indigo-600 hover:text-indigo-800"
          >
            Forgot password?
          </button>
          <span>
            Need an account?{' '}
            <button
              type="button"
              onClick={() => navigate('/register')}
              className="text-indigo-600 hover:text-indigo-800"
            >
              Sign up
            </button>
          </span>
        </div>
      </div>
    </div>
  );
};

export default LoginPage;
