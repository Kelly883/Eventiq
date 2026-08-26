import React from 'react';

const TrustSection = () => {
  const features = [
    {
      icon: (
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
          <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      ),
      title: 'AUTHENTIC TICKETS',
      description: 'Verified digital tickets.',
    },
    {
      icon: (
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
          <rect x="1" y="4" width="22" height="16" rx="2" ry="2" />
          <line x1="1" y1="10" x2="23" y2="10" />
        </svg>
      ),
      title: 'SECURE CHECKOUT',
      description: 'Secure payment processing.',
    },
    {
      icon: (
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
          <path d="M22 11.08V12a10 10 0 11-5.93-9.14" />
          <polyline points="22 4 12 14.01 9 11.01" />
        </svg>
      ),
      title: 'INSTANT DELIVERY',
      description: 'Receive your digital ticket after successful payment.',
    },
    {
      icon: (
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5">
          <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
          <rect x="7" y="7" width="3" height="3" />
          <rect x="14" y="7" width="3" height="3" />
          <rect x="7" y="14" width="3" height="3" />
          <rect x="14" y="14" width="3" height="3" />
        </svg>
      ),
      title: 'EASY ENTRY',
      description: 'Scan your QR code at the event.',
    },
  ];

  return (
    <section className="trust-section">
      <div className="section-container">
        <h2 className="section-title">Your ticket should be the easiest part.</h2>
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
