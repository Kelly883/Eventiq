import React, { useState } from 'react';
import { useSearchParams, useNavigate } from 'react-router-dom';
import { useAuthContext } from '../context/AuthContext';
import BrandLogo from '../../common/components/BrandLogo';
import './AuthPage.css';

const ResetPasswordPage = () => {
  const [searchParams] = useSearchParams();
  const token = searchParams.get('token');
  const email = searchParams.get('email');
  const navigate = useNavigate();
  const { resetPassword } = useAuthContext();

  const [password, setPassword] = useState('');
  const [passwordConfirm, setPasswordConfirm] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();

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
      await resetPassword(token, email, password);
      setSuccess(true);
    } catch (err) {
      setError(err.response?.data?.message || err.message || 'Failed to reset password.');
    } finally {
      setLoading(false);
    }
  };

  // Missing token — show error state
  if (!token) {
    return (
      <div className="auth-page">
        <div className="auth-page__orb auth-page__orb--coral" aria-hidden="true" />
        <div className="auth-page__orb auth-page__orb--teal" aria-hidden="true" />

        <section className="auth-page__content">
          <div className="auth-page__brand-row">
            <a href="/" className="auth-page__brand" aria-label="EventIQ home">
              <BrandLogo />
            </a>
          </div>

          <div className="auth-page__card">
            <div className="auth-page__intro">
              <h1>Invalid Link</h1>
              <p>This password reset link is invalid or has expired. Please request a new reset link.</p>
            </div>

            <button
              type="button"
              className="auth-page__submit"
              onClick={() => navigate('/forgot-password')}
            >
              Request New Reset Link
            </button>
          </div>
        </section>
      </div>
    );
  }

  return (
    <div className="auth-page">
      <div className="auth-page__orb auth-page__orb--coral" aria-hidden="true" />
      <div className="auth-page__orb auth-page__orb--teal" aria-hidden="true" />

      <section className="auth-page__content">
        <div className="auth-page__brand-row">
          <a href="/" className="auth-page__brand" aria-label="EventIQ home">
            <BrandLogo />
          </a>
          <span className="auth-page__secure-badge">Password Reset</span>
        </div>

        <div className="auth-page__card">
          <div className="auth-page__intro">
            <p>Create New Password</p>
            <h1>Reset Password</h1>
            <p>Enter your new password below.</p>
          </div>

          {error && (
            <div className="auth-page__alert auth-page__alert--error" role="alert">
              {error}
            </div>
          )}

          {success ? (
            <div className="auth-page__success">
              <div className="auth-page__alert auth-page__alert--success">
                Your password has been reset successfully.
              </div>
              <button
                type="button"
                className="auth-page__submit"
                onClick={() => navigate('/login')}
              >
                Continue to Login
              </button>
            </div>
          ) : (
            <form onSubmit={handleSubmit} className="auth-page__form" aria-busy={loading}>
              <div className="auth-page__field">
                <label htmlFor="reset-password">New Password</label>
                <input
                  id="reset-password"
                  type="password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="auth-page__input"
                  required
                  minLength={8}
                  autoComplete="new-password"
                  placeholder="Enter new password"
                  disabled={loading}
                />
              </div>

              <div className="auth-page__field">
                <label htmlFor="reset-password-confirm">Confirm New Password</label>
                <input
                  id="reset-password-confirm"
                  type="password"
                  value={passwordConfirm}
                  onChange={(e) => setPasswordConfirm(e.target.value)}
                  className="auth-page__input"
                  required
                  minLength={8}
                  autoComplete="new-password"
                  placeholder="Confirm new password"
                  disabled={loading}
                />
              </div>

              <button
                type="submit"
                disabled={loading}
                className="auth-page__submit"
              >
                {loading ? (
                  <>
                    <span className="auth-page__spinner" aria-hidden="true" />
                    Resetting...
                  </>
                ) : (
                  'Reset Password'
                )}
              </button>
            </form>
          )}

          <div className="auth-page__footer-link">
            Remember your password?{' '}
            <button
              type="button"
              className="auth-page__text-link"
              onClick={() => navigate('/login')}
            >
              Back to Login
            </button>
          </div>
        </div>

        <p className="auth-page__security-note">
          Your account is protected with secure authentication.
        </p>
      </section>
    </div>
  );
};

export default ResetPasswordPage;
