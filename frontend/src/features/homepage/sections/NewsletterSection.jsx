import React, { useState } from 'react';
import { api, showToast } from '../../../lib/api';

const NewsletterSection = () => {
  const [email, setEmail] = useState('');
  const [submitted, setSubmitted] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!email.trim()) return;

    setLoading(true);
    setError('');

    try {
      await api.post('/newsletter/subscribe', { email: email.trim() });
      setSubmitted(true);
      setEmail('');
      showToast('Subscribed', "You're on the list. We'll keep you updated.", 'success', 5000);
    } catch (err) {
      setError('Something went wrong. Please try again later.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <section className="newsletter-section">
      <div className="section-container">
        <div className="newsletter-content">
          <h2 className="newsletter-title">Don't miss what's next.</h2>
          <p className="newsletter-subtitle">
            Get updates about new events and experiences near you.
          </p>
          {submitted ? (
            <p className="newsletter-success">Thanks! We'll keep you updated.</p>
          ) : (
            <form className="newsletter-form" onSubmit={handleSubmit}>
              <input
                type="email"
                placeholder="Enter your email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="newsletter-input"
                required
                disabled={loading}
              />
              <button type="submit" className="btn-notify" disabled={loading}>
                {loading ? 'Subscribing...' : 'Notify me'}
              </button>
              {error && <p className="newsletter-error">{error}</p>}
            </form>
          )}
        </div>
      </div>
    </section>
  );
};

export default NewsletterSection;
