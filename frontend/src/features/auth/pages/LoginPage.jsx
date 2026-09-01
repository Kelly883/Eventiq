import React, { useState, useEffect } from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import { useAuthContext } from '../context/AuthContext';
import { showToast } from '../../../lib/api';
import { safeRedirectPath, defaultRedirect, normalizeFromPath } from '../utils';

const LoginPage = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [rememberMe, setRememberMe] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
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

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      await login(email, password, rememberMe);
      showToast('Session Extended', 'Your session will remain active.', 'info');
      // Compute redirect after login when user roles are available
      const fromPath = normalizeFromPath(location.state?.from) || sessionExpiredReturn || null;
      const redirectTo = safeRedirectPath(fromPath, user, defaultRedirect(user));
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
    <div className="relative isolate flex min-h-screen items-center justify-center overflow-hidden bg-[#F7F8FA] px-4 py-10 font-sans text-[#333333] sm:px-6">
      <div aria-hidden="true" className="absolute -left-24 top-0 -z-10 h-80 w-80 rounded-full bg-[#FF9E9E]/25 blur-3xl" />
      <div aria-hidden="true" className="absolute -bottom-32 -right-24 -z-10 h-96 w-96 rounded-full bg-[#4ECDC4]/20 blur-3xl" />

      <section className="w-full max-w-md">
        <div className="mb-8 flex items-center justify-between px-1">
          <Link to="/" className="flex items-center gap-3 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#FF6B6B] focus-visible:ring-offset-2">
            <span aria-hidden="true" className="flex h-10 w-10 items-center justify-center rounded-xl bg-[#FF6B6B] text-lg font-black text-white shadow-[0_4px_10px_rgba(255,107,107,0.25)]">E</span>
            <span className="text-xl font-bold tracking-tight text-[#333333]">Eventiq</span>
          </Link>
          <span className="rounded-full border border-[#4ECDC4]/40 bg-[#4ECDC4]/10 px-3 py-1 text-xs font-semibold tracking-wide text-[#287A74]">
            Secure sign-in
          </span>
        </div>

        <div className="rounded-xl border border-[#E3E4E6] bg-white p-6 shadow-[0_1px_3px_rgba(0,0,0,0.1)] sm:p-8">
          <div className="mb-8">
            <p className="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-[#FF6B6B]">Your Event Workspace</p>
            <h1 className="text-3xl font-bold leading-tight text-[#333333]">Welcome back</h1>
            <p className="mt-2 text-sm leading-6 text-[#777777]">Sign in to manage events, tickets, and check-ins.</p>
          </div>

          <form onSubmit={handleSubmit} className="space-y-5" aria-busy={loading}>
            <div>
              <label htmlFor="login-email" className="mb-2 block text-sm font-semibold text-[#333333]">
                Email address
              </label>
              <input
                id="login-email"
                name="email"
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="w-full rounded-lg border border-[#D1D2D4] bg-white px-4 py-3 text-sm text-[#333333] shadow-sm transition-colors placeholder:text-[#999999] focus-visible:border-[#FF6B6B] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#FF9E9E]/50 disabled:cursor-not-allowed disabled:bg-[#F7F8FA]"
                required
                disabled={loading}
                autoComplete="email"
                spellCheck={false}
                placeholder="you@example.com…"
              />
            </div>

            <div>
              <div className="mb-2 flex items-center justify-between gap-4">
                <label htmlFor="login-password" className="text-sm font-semibold text-[#333333]">
                  Password
                </label>
                <Link to="/forgot-password" className="text-sm font-semibold text-[#CC3838] underline-offset-4 hover:text-[#D94545] hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#FF6B6B] focus-visible:ring-offset-2">
                  Forgot password?
                </Link>
              </div>
              <div className="relative">
                <input
                  id="login-password"
                  name="password"
                  type={showPassword ? 'text' : 'password'}
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className={`w-full rounded-lg border bg-white px-4 py-3 pr-12 text-sm text-[#333333] shadow-sm transition-colors placeholder:text-[#999999] focus-visible:outline-none focus-visible:ring-2 disabled:cursor-not-allowed disabled:bg-[#F7F8FA] ${
                    error
                      ? 'border-[#FF6B6B] focus-visible:ring-[#FF9E9E]/50'
                      : 'border-[#D1D2D4] focus-visible:border-[#FF6B6B] focus-visible:ring-[#FF9E9E]/50'
                  }`}
                  required
                  disabled={loading}
                  autoComplete="current-password"
                  aria-invalid={Boolean(error)}
                  aria-describedby={error ? 'login-error' : undefined}
                  placeholder="Enter your password…"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword((visible) => !visible)}
                  disabled={loading}
                  aria-label={showPassword ? 'Hide password' : 'Show password'}
                  aria-pressed={showPassword}
                  className="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-lg text-xs font-semibold text-[#777777] hover:text-[#CC3838] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-[#FF6B6B] disabled:cursor-not-allowed"
                >
                  {showPassword ? 'Hide' : 'Show'}
                </button>
              </div>
              {error && (
                <p id="login-error" role="alert" className="mt-2 text-sm font-medium text-[#CC3838]">
                  {error}
                </p>
              )}
            </div>

            <label className="flex cursor-pointer items-center gap-3 text-sm text-[#555555]">
              <input
                name="remember-me"
                type="checkbox"
                checked={rememberMe}
                onChange={(e) => setRememberMe(e.target.checked)}
                disabled={loading}
                className="h-4 w-4 rounded border-[#D1D2D4] text-[#FF6B6B] focus:ring-[#FF6B6B] disabled:cursor-not-allowed"
              />
              Keep me signed in on this device
            </label>

            <button
              type="submit"
              disabled={loading}
              className="flex w-full items-center justify-center gap-2 rounded-lg bg-[#FF6B6B] px-6 py-3 text-base font-bold text-white shadow-[0_4px_6px_-1px_rgba(0,0,0,0.1),0_2px_4px_-1px_rgba(0,0,0,0.06)] transition-colors hover:bg-[#D94545] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#FF6B6B] focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:bg-[#FF9E9E]"
            >
              {loading && <span aria-hidden="true" className="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" />}
              {loading ? 'Signing in…' : 'Sign in to Eventiq'}
            </button>
          </form>

          <div className="mt-6 border-t border-[#E3E4E6] pt-5 text-center text-sm text-[#777777]">
            Need an account?{' '}
            <Link to="/register" className="font-semibold text-[#CC3838] underline-offset-4 hover:text-[#D94545] hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#FF6B6B] focus-visible:ring-offset-2">
              Create an account
            </Link>
          </div>
        </div>

        <p className="mt-6 text-center text-xs text-[#777777]">
          Your account is protected with secure authentication.
        </p>
      </section>
    </div>
  );
};

export default LoginPage;
