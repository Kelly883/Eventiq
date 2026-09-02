import React, { useState, useEffect } from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import { useAuthContext } from '../context/AuthContext';
import { showToast } from '../../../lib/api';
import BrandLogo from '../../common/components/BrandLogo';
import { safeRedirectPath, defaultRedirect, normalizeFromPath } from '../utils';
import './LoginPage.css';

const LoginPage = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [rememberMe, setRememberMe] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const navigate = useNavigate();
  const location = useLocation();
  const { login } = useAuthContext();

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
      const { user: authenticatedUser } = await login(email, password, rememberMe);
      showToast('Session Extended', 'Your session will remain active.', 'info');
      const fromPath = normalizeFromPath(location.state?.from) || sessionExpiredReturn || null;
      const redirectTo = safeRedirectPath(
        fromPath,
        authenticatedUser,
        defaultRedirect(authenticatedUser)
      );
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
    <div className="login-page">
      <div aria-hidden="true" className="login-page__orb login-page__orb--coral" />
      <div aria-hidden="true" className="login-page__orb login-page__orb--teal" />

      <section className="login-page__content">
        <div className="login-page__brand-row">
          <Link to="/" className="login-page__brand" aria-label="eventIQ home">
            <BrandLogo />
          </Link>
          <span className="login-page__secure-badge">
            Secure sign-in
          </span>
        </div>

        <div className="login-page__card">
          <div className="login-page__intro">
            <p>Your Event Workspace</p>
            <h1>Welcome back</h1>
            <p>Sign in to manage events, tickets, and check-ins.</p>
          </div>

          <form onSubmit={handleSubmit} className="login-page__form" aria-busy={loading}>
            <div className="login-page__field">
              <label htmlFor="login-email">
                Email address
              </label>
              <input
                id="login-email"
                name="email"
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="login-page__input"
                required
                disabled={loading}
                autoComplete="email"
                spellCheck={false}
                placeholder="you@example.com…"
              />
            </div>

            <div className="login-page__field">
              <div className="login-page__field-header">
                <label htmlFor="login-password">
                  Password
                </label>
                <Link to="/forgot-password" className="login-page__text-link">
                  Forgot password?
                </Link>
              </div>
              <div className="login-page__password-field">
                <input
                  id="login-password"
                  name="password"
                  type={showPassword ? 'text' : 'password'}
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className={`login-page__input ${error ? 'login-page__input--error' : ''}`}
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
                  className="login-page__password-toggle"
                >
                  {showPassword ? 'Hide' : 'Show'}
                </button>
              </div>
              {error && (
                <p id="login-error" role="alert" className="login-page__error">
                  {error}
                </p>
              )}
            </div>

            <label className="login-page__remember">
              <input
                name="remember-me"
                type="checkbox"
                checked={rememberMe}
                onChange={(e) => setRememberMe(e.target.checked)}
                disabled={loading}
                className="login-page__checkbox"
              />
              Keep me signed in on this device
            </label>

            <button
              type="submit"
              disabled={loading}
              className="login-page__submit"
            >
              {loading && <span aria-hidden="true" className="login-page__spinner" />}
              {loading ? 'Signing in…' : 'Sign in to Eventiq'}
            </button>
          </form>

          <div className="login-page__signup">
            Need an account?{' '}
            <Link to="/register" className="login-page__text-link">
              Create an account
            </Link>
          </div>
        </div>

        <p className="login-page__security-note">
          Your account is protected with secure authentication.
        </p>
      </section>
    </div>
  );
};

export default LoginPage;
