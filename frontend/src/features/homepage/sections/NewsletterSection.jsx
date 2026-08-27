import React, { useState } from 'react';
import { api, showToast } from '../../../lib/api';

const NewsletterSection = () => {
  const [email, setEmail] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [status, setStatus] = useState(null); // 'success' | 'error' | null

  // Previously this section flipped local state and claimed "Thanks! We'll
  // keep you updated." without persisting anything. It now POSTs to a real
  // backend endpoint (POST /api/newsletter/subscribe) and only shows a success
  // message when the request actually succeeds.
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
    <section className="newsletter-section">
      <div className="section-container">
        <div className="newsletter-content">
          <h2 className="newsletter-title">Don't miss what's next.</h2>
          <p className="newsletter-subtitle">
            Get updates about new events and experiences near you.
          </p>
          {status === 'success' ? (
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
