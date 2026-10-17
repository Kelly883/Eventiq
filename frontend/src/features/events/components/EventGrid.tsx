import React from 'react';
import EventCard from './EventCard';
import type { Event } from '../types/shared';

export interface EventGridProps {
  events: Event[];
  isLoading: boolean;
  hasMore: boolean;
  onLoadMore?: () => void;
  onEventClick?: (event: Event) => void;
}

const getColumnCount = (): number => {
  if (typeof window === 'undefined') return 1;
  const width = window.innerWidth;
  if (width >= 1024) return 3;
  if (width >= 768) return 2;
  return 1;
};

const EventGrid = ({
  events,
  isLoading,
  hasMore,
  onLoadMore,
  onEventClick,
}: EventGridProps) => {
  const columnCount = getColumnCount();

  if (isLoading) {
    return (
      <div
        className="event-grid"
        style={{ gridTemplateColumns: `repeat(${columnCount}, 1fr)` }}
        aria-label="Loading events"
      >
        {Array.from({ length: 6 }).map((_, i) => (
          <div
            key={i}
            className="event-card skeleton-card"
            style={{ marginBottom: '1rem' }}
            aria-hidden="true"
          >
            <div className="skeleton-image-lg" />
            <div className="skeleton-title-lg" />
            <div className="skeleton-text-sm" />
            <div className="skeleton-text-xs" />
            <div className="skeleton-text-sm" />
          </div>
        ))}
      </div>
    );
  }

  if (events.length === 0) {
    return (
      <div
        className="event-grid"
        style={{ gridTemplateColumns: `repeat(${columnCount}, 1fr)` }}
      >
        <div className="empty-state">
          <svg
            className="empty-state__icon"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.5"
            aria-hidden="true"
          >
            <circle cx="12" cy="12" r="10" />
            <line x1="8" y1="12" x2="16" y2="12" />
            <line x1="9" y1="9" x2="15" y2="15" />
            <line x1="9" y1="15" x2="15" y2="9" />
          </svg>
          <h2 className="empty-state__title">No events found</h2>
          <p className="empty-state__description">
            Try adjusting your search or filters.
          </p>
          {hasMore && onLoadMore && (
            <button
              className="empty-state__load-more"
              onClick={onLoadMore}
              aria-label="Load more events"
            >
              Load More
            </button>
          )}
        </div>
      </div>
    );
  }

  return (
    <div
      className="event-grid"
      style={{ gridTemplateColumns: `repeat(${columnCount}, 1fr)` }}
    >
      {events.map((event) => (
        <EventCard
          key={event.id}
          event={event}
          onClick={() => onEventClick?.(event)}
        />
      ))}
    </div>
  );
};

export default React.memo(EventGrid);
