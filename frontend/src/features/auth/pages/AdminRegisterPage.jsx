import React, { useState, useEffect } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuthContext } from '../context/AuthContext';
import { api, showToast, clearToasts } from '../../../lib/api';
import BrandLogo from '../../common/components/BrandLogo';
import './AdminRegisterPage.css';

const AdminRegisterPage = () => {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirm, setPasswordConfirm] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [showPasswordConfirm, setShowPasswordConfirm] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [checking, setChecking] = useState(true);
  const navigate = useNavigate();
  const { adminSetup } = useAuthContext();

  useEffect(() => {
    clearToasts();

    const checkAdminExists = async () => {
      try {
        const res = await api.get('/auth/admin-setup');
        const required = res.data?.required === true;

        if (!required) {
          showToast(
            'Admin exists',
            'An administrator account already exists. Please sign in.',
            'warning',
            5000
          );
          navigate('/login', { replace: true });
        }
      } catch (err) {
        const status = err.response?.status;

        if (status === 404) {
          showToast(
            'Admin exists',
            'An administrator account already exists. Please sign in.',
            'warning',
            5000
          );
          navigate('/login', { replace: true });
          return;
        }

        if (status === 429) {
          showToast(
            'Too many attempts',
            'Please wait a moment before trying again.',
            'warning',
            5000
          );
        }
      } finally {
        setChecking(false);
      }
    };

    checkAdminExists();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [navigate]);

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
      const res = await adminSetup(name, email, password, passwordConfirm);
      const msg = res?.message || 'Administrator account created successfully. You may now sign in.';
      navigate('/login', { state: { message: msg, messageType: 'success' } });
    } catch (err) {
      const status = err.response?.status;

      if (status === 404) {
        const msg = 'An administrator account already exists. Please sign in.';
        setError(msg);
        showToast('Admin exists', msg, 'warning', 5000);
        setTimeout(() => navigate('/login', { replace: true }), 2000);
        return;
      }

      if (!err.response) {
        const friendly = 'Network error. Please check your connection and try again.';
        setError(friendly);
        showToast('Network error', friendly, 'error');
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
            'Failed to create administrator account.'
        );
      }
    } finally {
      setLoading(false);
    }
  };

  if (checking) {
    return (
      <div className="admin-register-page">
        <div className="admin-register-page__content">
          <div className="admin-register-page__brand-row">
            <BrandLogo />
          </div>
          <div className="admin-register-page__card">
            <p className="admin-register-page__loading">Checking setup status...</p>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="admin-register-page">
      <div aria-hidden="true" className="admin-register-page__orb admin-register-page__orb--coral" />
      <div aria-hidden="true" className="admin-register-page__orb admin-register-page__orb--teal" />

      <section className="admin-register-page__content">
        <div className="admin-register-page__brand-row">
          <Link to="/" className="admin-register-page__brand" aria-label="eventIQ home">
            <BrandLogo />
          </Link>
          <span className="admin-register-page__secure-badge">
            Platform Setup
          </span>
        </div>

        <div className="admin-register-page__card">
          <div className="admin-register-page__intro">
            <p>Eventiq</p>
            <h1>Create administrator account</h1>
            <p>This page is only available before the first admin account is created.</p>
          </div>

          <form onSubmit={handleSubmit} className="admin-register-page__form" aria-busy={loading}>
            <div className="admin-register-page__field">
              <label htmlFor="admin-register-name">
                Full Name
              </label>
              <input
                id="admin-register-name"
                name="name"
                type="text"
                value={name}
                onChange={(e) => setName(e.target.value)}
                className="admin-register-page__input"
                required
                autoComplete="name"
                placeholder="Jane Doe"
              />
            </div>

            <div className="admin-register-page__field">
              <label htmlFor="admin-register-email">
                Email address
              </label>
              <input
                id="admin-register-email"
                name="email"
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="admin-register-page__input"
                required
                autoComplete="email"
                placeholder="you@example.com"
              />
            </div>

            <div className="admin-register-page__field">
              <div className="admin-register-page__field-header">
                <label htmlFor="admin-register-password">
                  Password
                </label>
              </div>
              <div className="admin-register-page__password-field">
                <input
                  id="admin-register-password"
                  name="password"
                  type={showPassword ? 'text' : 'password'}
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="admin-register-page__input"
                  required
                  minLength={8}
                  autoComplete="new-password"
                  placeholder="Create a password"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword((visible) => !visible)}
                  disabled={loading}
                  className="admin-register-page__password-toggle"
                  aria-label={showPassword ? 'Hide password' : 'Show password'}
                  aria-pressed={showPassword}
                >
                  {showPassword ? 'Hide' : 'Show'}
                </button>
              </div>
            </div>

            <div className="admin-register-page__field">
              <div className="admin-register-page__field-header">
                <label htmlFor="admin-register-password-confirm">
                  Confirm Password
                </label>
              </div>
              <div className="admin-register-page__password-field">
                <input
                  id="admin-register-password-confirm"
                  name="passwordConfirm"
                  type={showPasswordConfirm ? 'text' : 'password'}
                  value={passwordConfirm}
                  onChange={(e) => setPasswordConfirm(e.target.value)}
                  className="admin-register-page__input"
                  required
                  minLength={8}
                  autoComplete="new-password"
                  placeholder="Repeat your password"
                />
                <button
                  type="button"
                  onClick={() => setShowPasswordConfirm((visible) => !visible)}
                  disabled={loading}
                  className="admin-register-page__password-toggle"
                  aria-label={showPasswordConfirm ? 'Hide password' : 'Show password'}
                  aria-pressed={showPasswordConfirm}
                >
                  {showPasswordConfirm ? 'Hide' : 'Show'}
                </button>
              </div>
            </div>

            {error && (
              <p id="admin-register-error" role="alert" className="admin-register-page__error">
                {error}
              </p>
            )}

            <button
              type="submit"
              disabled={loading}
              className="admin-register-page__submit"
            >
              {loading && <span aria-hidden="true" className="admin-register-page__spinner" />}
              {loading ? 'Creating account...' : 'Create Administrator Account'}
            </button>
          </form>

          <div className="admin-register-page__signin">
            Already have an account?{' '}
            <Link to="/login" className="admin-register-page__text-link">
              Sign in
            </Link>
          </div>
        </div>

        <p className="admin-register-page__security-note">
          This page is disabled after the first admin is created.
        </p>
      </section>
    </div>
  );
};

export default AdminRegisterPage;
