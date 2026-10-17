import React, { useEffect, useState } from 'react';
import type { PricingWindowData } from '../types/shared';

export interface PricingWindowProps {
  pricing: PricingWindowData;
  currency?: string;
}

function formatCountdown(dateString: string | Date | null | undefined): string {
  if (!dateString) return '';

  const changeTime =
    dateString instanceof Date
      ? dateString.getTime()
      : new Date(dateString).getTime();
  const now = new Date().getTime();
  const diff = changeTime - now;

  if (diff <= 0) return 'Now';

  const totalSeconds = Math.floor(diff / 1000);
  const totalMinutes = Math.floor(totalSeconds / 60);
  const totalHours = Math.floor(totalMinutes / 60);
  const days = Math.floor(totalHours / 24);
  const remainingHours = totalHours % 24;
  const remainingMinutes = totalMinutes % 60;

  if (days > 0) {
    return `in ${days} ${days === 1 ? 'day' : 'days'}`;
  }
  if (remainingHours > 0) {
    return `in ${remainingHours} ${remainingHours === 1 ? 'hour' : 'hours'}`;
  }
  if (remainingMinutes > 0) {
    return `in ${remainingMinutes} min`;
  }
  return 'in less than a minute';
}

function getCurrencySymbol(currency?: string | null): string {
  const symbols: Record<string, string> = {
    USD: '$',
    EUR: '€',
    GBP: '£',
    NGN: '₦',
    GHS: '₵',
    KES: 'KSh',
    ZAR: 'R',
  };
  return symbols[(currency || 'USD').toUpperCase()] || '₦';
}

function formatPrice(
  price: number | string | null | undefined,
  currency?: string | null,
): string {
  if (price == null) return '—';
  const num = typeof price === 'string' ? parseFloat(price) : price;
  if (Number.isNaN(num)) return '—';
  const symbol = getCurrencySymbol(currency);
  return `${symbol}${num.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 })}`;
}

const PricingWindow = ({
  pricing,
  currency = 'NGN',
}: PricingWindowProps) => {
  const { currentPrice, nextPrice, nextPriceChangeAt } = pricing;
  const [countdown, setCountdown] = useState<string>(() =>
    formatCountdown(nextPriceChangeAt),
  );

  useEffect(() => {
    const timer = setInterval(() => {
      setCountdown(formatCountdown(nextPriceChangeAt));
    }, 1000);
    return () => clearInterval(timer);
  }, [nextPriceChangeAt]);

  const hasNextPrice = nextPrice != null && nextPriceChangeAt != null;

  return (
    <div className="pricing-window" aria-label="Pricing information">
      <div className="pricing-window__current">
        <span className="pricing-window__label">Current Price</span>
        <span className="pricing-window__value">
          {formatPrice(currentPrice, currency)}
        </span>
      </div>
      {hasNextPrice && (
        <>
          <div className="pricing-window__next">
            <span className="pricing-window__label">Next Price</span>
            <span className="pricing-window__value">
              {formatPrice(nextPrice, currency)}
            </span>
          </div>
          <div className="pricing-window__countdown">
            <span className="pricing-window__countdown-text">
              Price changes {countdown}
            </span>
          </div>
        </>
      )}
    </div>
  );
};

export default PricingWindow;
