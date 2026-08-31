import React from 'react';

const TrustSection = () => {
  const features = [
    {
      icon: (
        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" aria-hidden="true">
          <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      ),
      title: 'Authentic tickets',
      description: 'Verified digital tickets with a unique QR code.',
    },
    {
      icon: (
        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" aria-hidden="true">
          <rect x="1" y="4" width="22" height="16" rx="2" ry="2" />
          <line x1="1" y1="10" x2="23" y2="10" />
        </svg>
      ),
      title: 'Secure checkout',
      description: 'Your payment details are protected with every order.',
    },
    {
      icon: (
        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" aria-hidden="true">
          <path d="M22 11.08V12a10 10 0 11-5.93-9.14" />
          <polyline points="22 4 12 14.01 9 11.01" />
        </svg>
      ),
      title: 'Instant delivery',
      description: 'Your ticket lands in your inbox the moment you pay.',
    },
    {
      icon: (
        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" aria-hidden="true">
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
          <rect x="7" y="7" width="3" height="3" />
          <rect x="14" y="7" width="3" height="3" />
          <rect x="7" y="14" width="3" height="3" />
          <rect x="14" y="14" width="3" height="3" />
        </svg>
      ),
      title: 'Easy entry',
      description: 'Flash your QR code at the gate and walk straight in.',
    },
  ];

  return (
    <section className="trust-section">
      <div className="section-container">
        <div className="section-header section-header-center">
          <div>
            <p className="section-eyebrow">Why EventIQ</p>
            <h2 className="section-title">Your ticket should be the easiest part.</h2>
            <p className="section-subtitle">Everything between you and a great night, handled.</p>
          </div>
        </div>
        <div className="trust-features">
          {features.map((feature, index) => (
            <div key={index} className="trust-feature">
              <div className="trust-icon">{feature.icon}</div>
              <h3 className="trust-title">{feature.title}</h3>
              <p className="trust-description">{feature.description}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};

export default TrustSection;