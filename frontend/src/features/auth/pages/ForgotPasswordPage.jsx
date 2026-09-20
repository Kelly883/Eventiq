import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuthContext } from '../context/AuthContext';
import BrandLogo from '../../common/components/BrandLogo';
import './AuthPage.css';

const ForgotPasswordPage = () => {
  const [email, setEmail] = useState('');
  const [loading, setLoading] = useState(false);
  const [submitted, setSubmitted] = useState(false);
  const [error, setError] = useState('');
  const navigate = useNavigate();
  const { forgotPassword } = useAuthContext();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      await forgotPassword(email);
      setSubmitted(true);
    } catch (err) {
      setError(
        err.response?.data?.message ||
        err.message ||
        'Failed to send reset email.'
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="auth-page">
      <div className="auth-page__orb auth-page__orb--coral" aria-hidden="true" />
      <div className="auth-page__orb auth-page__orb--teal" aria-hidden="true" />

      <section className="auth-page__content">
        <div className="auth-page__brand-row">
          <a href="/" className="auth-page__brand" aria-label="EventIQ home">
            <BrandLogo />
          </a>
          <span className="auth-page__secure-badge">Password Recovery</span>
        </div>

        <div className="auth-page__card">
          <div className="auth-page__intro">
            <p>Account Recovery</p>
            <h1>Forgot Password</h1>
            <p>Enter your email address and we'll send you a link to reset your password.</p>
          </div>

          {error && (
            <div className="auth-page__alert auth-page__alert--error" role="alert">
              {error}
            </div>
          )}

          {submitted ? (
            <div className="auth-page__success">
              <div className="auth-page__alert auth-page__alert--success">
                Password reset instructions have been sent to <strong>{email}</strong>. Check your inbox (and spam folder).
              </div>
              <button
                type="button"
                className="auth-page__submit"
                onClick={() => navigate('/login')}
              >
                Back to Login
              </button>
            </div>
          ) : (
            <form onSubmit={handleSubmit} className="auth-page__form" aria-busy={loading}>
              <div className="auth-page__field">
                <label htmlFor="forgot-email">Email Address</label>
                <input
                  id="forgot-email"
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  className="auth-page__input"
                  required
                  autoComplete="email"
                  placeholder="you@example.com"
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
                    Sending...
                  </>
                ) : (
                  'Send Reset Link'
                )}
              </button>
            </form>
          )}

          {!submitted && (
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
          )}
        </div>

        <p className="auth-page__security-note">
          Your account is protected with secure authentication.
        </p>
      </section>
    </div>
  );
};

export default ForgotPasswordPage;
