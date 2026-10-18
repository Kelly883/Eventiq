import React, { useEffect, useMemo, useState } from 'react';
import { Skeleton, Spinner } from '../events/components/Skeletons';

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  if (Number.isNaN(date.getTime())) return 'N/A';
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
};

const formatCountdown = (ms) => {
  if (ms <= 0) return 'Now';
  const totalSeconds = Math.floor(ms / 1000);
  const days = Math.floor(totalSeconds / 86400);
  const hours = Math.floor((totalSeconds % 86400) / 3600);
  const minutes = Math.floor((totalSeconds % 3600) / 60);
  const seconds = totalSeconds % 60;
  if (days > 0) return `${days}d ${hours}h ${minutes}m`;
  if (hours > 0) return `${hours}h ${minutes}m ${seconds}s`;
  return `${minutes}m ${seconds}s`;
};

const PricingPreviewTimeline = ({ pricePoints, onPointClick, loading = false, error = null }) => {
  const [now, setNow] = useState(Date.now());

  useEffect(() => {
    const timer = setInterval(() => setNow(Date.now()), 1000);
    return () => clearInterval(timer);
  }, []);

  const sortedPoints = useMemo(
    () =>
      [...(pricePoints || [])].sort(
        (a, b) => new Date(a.date).getTime() - new Date(b.date).getTime()
      ),
    [pricePoints]
  );

  if (loading) {
    return (
      <div className="pricing-preview-timeline skeleton" style={{ height: '200px' }}>
        <div className="skeleton-line" style={{ width: '100%', height: '20px', marginBottom: '8px' }} />
        <div className="skeleton-line" style={{ width: '80%', height: '20px', marginBottom: '8px' }} />
        <div className="skeleton-line" style={{ width: '60%', height: '20px' }} />
      </div>
    );
  }

  if (error) {
    return (
      <div className="pricing-preview-timeline error">
        <span className="error-message">{error}</span>
      </div>
    );
  }

  if (sortedPoints.length === 0) {
    return (
      <div className="pricing-preview-timeline empty">
        <span>No price points to display</span>
      </div>
    );
  }

  const currentPoint = sortedPoints.find((p) => p.isActive) || sortedPoints[0];
  const nextPoint = sortedPoints.find((p) => p.isUpcoming);
  const countdownMs = nextPoint ? new Date(nextPoint.date).getTime() - now : 0;

  return (
    <div className="pricing-preview-timeline">
      <div className="pricing-preview-timeline__current">
        <span className="pricing-preview-timeline__current-label">Current Price</span>
        <span className="pricing-preview-timeline__current-value">
          ${currentPoint?.price ?? 0}
        </span>
      </div>

      {nextPoint && (
        <div className="pricing-preview-timeline__countdown" aria-live="polite">
          Next price change in <strong>{formatCountdown(countdownMs)}</strong>
          <span className="pricing-preview-timeline__countdown-target">
            (to ${nextPoint.price} on {formatDate(nextPoint.date)})
          </span>
        </div>
      )}

      <ol className="pricing-preview-timeline__list">
        {sortedPoints.map((point, i) => {
          const isActive = point.isActive;
          const isUpcoming = point.isUpcoming;
          const status = isActive ? 'active' : isUpcoming ? 'upcoming' : 'past';
          const statusClass = status; // will be used as CSS class suffix

          return (
            <li
              key={point.id ?? i}
              className={`pricing-preview-timeline__point pricing-preview-timeline__point--${status}`}
              onClick={() => onPointClick?.(point, i)}
              onKeyDown={(e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                  e.preventDefault();
                  onPointClick?.(point, i);
                }
              }}
              role="button"
              tabIndex={0}
              aria-label={`${point.name || 'Price point'}: $${point.price}, ${formatDate(point.date)}, ${status}`}
            >
              <span className="pricing-preview-timeline__dot" aria-hidden="true" />
              <div className="pricing-preview-timeline__point-content">
                <span className="pricing-preview-timeline__point-name">
                  {point.name || `Price point ${i + 1}`}
                </span>
                <span className="pricing-preview-timeline__point-meta">
                  <span className="pricing-preview-timeline__point-price">${point.price}</span>
                  <span className="pricing-preview-timeline__point-date">{formatDate(point.date)}</span>
                </span>
                <span className="pricing-preview-timeline__point-status">
                  {status === 'active' ? 'Active' : status === 'upcoming' ? 'Upcoming' : 'Past'}
                </span>
              </div>
            </li>
          );
        })}
      </ol>
    </div>
  );
};

PricingPreviewTimeline.defaultProps = {
  loading: false,
  error: null,
  pricePoints: [],
};

export default PricingPreviewTimeline;