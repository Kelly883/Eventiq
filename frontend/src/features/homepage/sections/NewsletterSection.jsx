import React, { useState } from 'react';
import { api, showToast } from '../../../lib/api';

const NewsletterSection = () => {
  const [email, setEmail] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [status, setStatus] = useState(null); // 'success' | 'error' | null

  // POSTs to a real backend endpoint (POST /api/newsletter/subscribe) and only
  // shows a success message when the request actually succeeds.
  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!email) return;

    setSubmitting(true);
    setStatus(null);
    try {
      await api.post('/newsletter/subscribe', { email });
      setStatus('success');
      setEmail('');
      showToast('Subscribed', "You're on the list. We'll keep you updated.", 'success', 5000);
    } catch {
      setStatus('error');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <section className="newsletter-section" aria-labelledby="newsletter-heading">
      <div className="section-container">
        <div className="newsletter-panel">
          <div className="newsletter-copy">
            <h2 className="newsletter-title" id="newsletter-heading">Don't miss what's next.</h2>
            <p className="newsletter-subtitle">
              Get a digest of new events and experiences near you.
            </p>
          </div>
          {status === 'success' ? (
            <p className="newsletter-success" role="status">Thanks! We'll keep you updated.</p>
          ) : (
            <form className="newsletter-form" onSubmit={handleSubmit}>
              <input
                type="email"
                name="email"
                placeholder="you@example.com"
                aria-label="Email address"
                autoComplete="email"
                spellCheck={false}
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="newsletter-input"
                required
                disabled={submitting}
              />
              <button type="submit" className="btn-notify" disabled={submitting}>
                {submitting ? 'Subscribing…' : 'Notify me'}
              </button>
            </form>
          )}
          {status === 'error' && (
            <p className="newsletter-error" role="alert">
              Something went wrong. Please try again in a moment.
            </p>
          )}
        </div>
      </div>
    </section>
  );
};

export default NewsletterSection;