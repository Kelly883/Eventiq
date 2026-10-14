import React from 'react';

export interface PricingWindowProps {
  pricing: {
    currentPrice: number;
    nextPrice: number;
    nextPriceChangeAt: string | null;
  };
}

const formatCountdown = (dateString: string | null): string => {
  if (!dateString) return '';

  const changeTime = new Date(dateString).getTime();
  const now = new Date().getTime();
  const diff = changeTime - now;

  if (diff <= 0) return 'Now';

  const hours = Math.floor(diff / (1000 * 60 * 60));
  const days = Math.floor(hours / 24);
  const remainingHours = hours % 24;

  if (days > 0) {
    return `in ${days} ${days === 1 ? 'day' : 'days'}`;
  }
  if (remainingHours > 0) {
    return `in ${remainingHours} ${remainingHours === 1 ? 'hour' : 'hours'}`;
  }
  return 'in less than an hour';
};

const PricingWindow = ({ pricing }: PricingWindowProps) => {
  const { currentPrice, nextPrice, nextPriceChangeAt } = pricing;
  const countdown = formatCountdown(nextPriceChangeAt);

  return (
    <div className="pricing-window">
      <div className="pricing-window__current">
        <span className="pricing-window__label">Current Price</span>
        <span className="pricing-window__value">₦{currentPrice.toLocaleString()}</span>
      </div>
      <div className="pricing-window__next">
        <span className="pricing-window__label">Next Price</span>
        <span className="pricing-window__value">₦{nextPrice.toLocaleString()}</span>
      </div>
      <div className="pricing-window__countdown">
        {countdown ? (
          <span className="pricing-window__countdown-text">{countdown}</span>
        ) : null}
      </div>
    </div>
  );
};

export default PricingWindow;