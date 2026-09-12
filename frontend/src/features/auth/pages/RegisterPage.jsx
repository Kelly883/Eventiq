import React, { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuthContext } from '../context/AuthContext';
import { showToast } from '../../../lib/api';
import BrandLogo from '../../common/components/BrandLogo';
import './RegisterPage.css';

const RegisterPage = () => {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirm, setPasswordConfirm] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [showPasswordConfirm, setShowPasswordConfirm] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const navigate = useNavigate();
  const { register } = useAuthContext();

  // TEMP: eruda console for on-device debugging — remove before committing
  useEffect(() => {
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/eruda';
    script.async = true;
    script.onload = () => {
      try {
        window.eruda?.init();
      } catch (_) {}
    };
    document.body.appendChild(script);
    return () => {
      try {
        window.eruda?.destroy();
      } catch (_) {}
      script.remove();
    };
  }, []);

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (!name.trim()) {
      setError('Please enter your full name.');
      return;
    }

    if (password !== passwordConfirm) {
      setError('Passwords do not match.');
      return;
    }

    if (password.length < 8) {
      setError('Password must be at least 8 characters.');
      return;
    }

    setLoading(true);
    setError('');

    try {
      const res = await register(email, password, name, passwordConfirm);
      // Backend returns generic message to avoid enumeration — same whether
      // email was new or already existed
      const msg = res?.message || 'If this email is not already registered, an account has been created. Please check your email to continue.';
      navigate('/login', { state: { message: msg, messageType: 'success' } });
    } catch (err) {
      // Distinct handling for network vs validation vs rate-limit vs CSRF.
      // Axios network errors have no response (e.g., CORS block, backend down,
      // wrong VITE_API_BASE_URL). err.message is often bare "Network Error".
      const status = err.response?.status;
      if (!err.response) {
        const friendly = 'Network error. Please check your connection and try again.';
        setError(friendly);
        showToast('Network error', friendly, 'error');
      } else if (status === 419) {
        const msg = 'Security token expired. Please refresh the page and try again.';
        setError(msg);
        showToast('Session expired', msg, 'warning');
      } else if (status === 429) {
        const msg = err.response?.data?.message || 'Too many attempts. Please wait a moment and try again.';
        setError(msg);
        showToast('Too many attempts', msg, 'warning');
      } else if (status === 422) {
        const validationMsg =
          err.response?.data?.errors?.email?.[0] ||
          err.response?.data?.errors?.password?.[0] ||
          err.response?.data?.errors?.name?.[0] ||
          err.response?.data?.message ||
          'Please check your details and try again.';
        setError(validationMsg);
      } else {
        setError(
          err.response?.data?.message ||
          err.response?.data?.errors?.email?.[0] ||
          err.message ||
          'Failed to create account.'
        );
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="register-page">
      <div aria-hidden="true" className="register-page__orb register-page__orb--coral" />
      <div aria-hidden="true" className="register-page__orb register-page__orb--teal" />

      <section className="register-page__content">
        <div className="register-page__brand-row">
          <Link to="/" className="register-page__brand" aria-label="eventIQ home">
            <BrandLogo />
          </Link>
          <span className="register-page__secure-badge">
            Create account
          </span>
        </div>

        <div className="register-page__card">
          <div className="register-page__intro">
            <p>Join Eventiq</p>
            <h1>Create your account</h1>
            <p>Start creating and managing amazing events.</p>
          </div>

          <form onSubmit={handleSubmit} className="register-page__form" aria-busy={loading}>
            <div className="register-page__field">
              <label htmlFor="register-name">
                Full Name
              </label>
              <input
                id="register-name"
                name="name"
                type="text"
                value={name}
                onChange={(e) => setName(e.target.value)}
                className="register-page__input"
                required
                autoComplete="name"
                placeholder="Jane Doe"
              />
            </div>

            <div className="register-page__field">
              <label htmlFor="register-email">
                Email address
              </label>
              <input
                id="register-email"
                name="email"
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="register-page__input"
                required
                autoComplete="email"
                placeholder="you@example.com"
              />
            </div>

            <div className="register-page__field">
              <div className="register-page__field-header">
                <label htmlFor="register-password">
                  Password
                </label>
              </div>
              <div className="register-page__password-field">
                <input
                  id="register-password"
                  name="password"
                  type={showPassword ? 'text' : 'password'}
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="register-page__input"
                  required
                  minLength={8}
                  autoComplete="new-password"
                  placeholder="Create a password"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword((visible) => !visible)}
                  disabled={loading}
                  className="register-page__password-toggle"
                  aria-label={showPassword ? 'Hide password' : 'Show password'}
                  aria-pressed={showPassword}
                >
                  {showPassword ? 'Hide' : 'Show'}
                </button>
              </div>
            </div>

            <div className="register-page__field">
              <div className="register-page__field-header">
                <label htmlFor="register-password-confirm">
                  Confirm Password
                </label>
              </div>
              <div className="register-page__password-field">
                <input
                  id="register-password-confirm"
                  name="passwordConfirm"
                  type={showPasswordConfirm ? 'text' : 'password'}
                  value={passwordConfirm}
                  onChange={(e) => setPasswordConfirm(e.target.value)}
                  className="register-page__input"
                  required
                  minLength={8}
                  autoComplete="new-password"
                  placeholder="Repeat your password"
                />
                <button
                  type="button"
                  onClick={() => setShowPasswordConfirm((visible) => !visible)}
                  disabled={loading}
                  className="register-page__password-toggle"
                  aria-label={showPasswordConfirm ? 'Hide password' : 'Show password'}
                  aria-pressed={showPasswordConfirm}
                >
                  {showPasswordConfirm ? 'Hide' : 'Show'}
                </button>
              </div>
            </div>

            {error && (
              <p id="register-error" role="alert" className="register-page__error">
                {error}
              </p>
            )}

            <button
              type="submit"
              disabled={loading}
              className="register-page__submit"
            >
              {loading && <span aria-hidden="true" className="register-page__spinner" />}
              {loading ? 'Creating account...' : 'Create Account'}
            </button>
          </form>

          <div className="register-page__signin">
            Already have an account?{' '}
            <Link to="/login" className="register-page__text-link">
              Sign in
            </Link>
          </div>
        </div>

        <p className="register-page__security-note">
          Your account is protected with secure authentication.
        </p>
      </section>
    </div>
  );
};

export default RegisterPage;
