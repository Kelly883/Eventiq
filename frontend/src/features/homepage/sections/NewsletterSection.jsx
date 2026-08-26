import React, { useState } from 'react';

const NewsletterSection = () => {
  const [email, setEmail] = useState('');
  const [submitted, setSubmitted] = useState(false);

  const handleSubmit = (e) => {
    e.preventDefault();
    if (email) {
      setSubmitted(true);
      setEmail('');
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
              />
              <button type="submit" className="btn-notify">
                Notify me
              </button>
            </form>
          )}
        </div>
      </div>
    </section>
  );
};

export default NewsletterSection;
