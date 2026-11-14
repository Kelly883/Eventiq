import React, { useState, useEffect } from 'react';

interface SocialProofProps {
  eventId: number;
  ticketTiers?: Array<{ quantity?: number; sold_count?: number }>;
  className?: string;
}

const SocialProof = ({ eventId, ticketTiers = [], className = '' }: SocialProofProps) => {
  const [viewers, setViewers] = useState(0);
  const [ticketsSoldToday, setTicketsSoldToday] = useState(0);
  const [showViewers, setShowViewers] = useState(true);

  // Simulate real-time data (in production, this would come from WebSocket or polling)
  useEffect(() => {
    // Initial random values based on eventId for consistency
    const baseViewers = (eventId * 7 + 13) % 50 + 5;
    const baseSoldToday = (eventId * 11 + 7) % 30 + 3;
    
    setViewers(baseViewers);
    setTicketsSoldToday(baseSoldToday);

    // Simulate real-time updates every 30 seconds
    const interval = setInterval(() => {
      setViewers((prev) => Math.max(0, prev + Math.floor(Math.random() * 3) - 1));
      setTicketsSoldToday((prev) => prev + Math.floor(Math.random() * 2));
    }, 30000);

    // Toggle between viewers and tickets sold every 5 seconds
    const toggleInterval = setInterval(() => {
      setShowViewers((prev) => !prev);
    }, 5000);

    return () => {
      clearInterval(interval);
      clearInterval(toggleInterval);
    };
  }, [eventId]);

  const totalSold = ticketTiers.reduce((sum, tier) => sum + (tier.sold_count || 0), 0);
  const totalCapacity = ticketTiers.reduce((sum, tier) => sum + (tier.quantity || 0), 0);
  const percentSold = totalCapacity > 0 ? Math.round((totalSold / totalCapacity) * 100) : 0;

  return (
    <div className={`social-proof ${className}`} aria-live="polite" aria-atomic="true">
      {showViewers ? (
        <span className="social-proof__item social-proof__item--viewers">
          <svg
            width="14"
            height="14"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
          >
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
            <circle cx="9" cy="7" r="4" />
            <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
          </svg>
          <span>{viewers} {viewers === 1 ? 'person' : 'people'} viewing</span>
        </span>
      ) : (
        <span className="social-proof__item social-proof__item--sold">
          <svg
            width="14"
            height="14"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
          >
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="6" x2="12" y2="12" />
            <line x1="12" y1="16" x2="12.01" y2="16" />
          </svg>
          <span>{ticketsSoldToday} tickets sold today</span>
        </span>
      )}
      {totalCapacity > 0 && (
        <span className="social-proof__item social-proof__item--percent">
          <svg
            width="14"
            height="14"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
          >
            <path d="M12 2a10 10 0 0 1 10 10" />
            <path d="M12 16v-6l4 4" />
          </svg>
          <span>{percentSold}% sold</span>
        </span>
      )}
    </div>
  );
};

export default SocialProof;