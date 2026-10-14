import React from 'react';
import EventCard from './EventCard';
import { Skeleton } from './Skeletons.jsx';

export interface EventGridProps {
  events: import('../types/shared').Event[];
  isLoading: boolean;
  hasMore: boolean;
  onLoadMore?: () => void;
  onEventClick?: (event: import('../types/shared').Event) => void;
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
      <div className="event-grid" style={{ gridTemplateColumns: `repeat(${columnCount}, 1fr)` }}>
        {Array.from({ length: 6 }).map((_, i) => (
          <div key={i} className="event-card skeleton" style={{ marginBottom: '1rem' }} />
        ))}
      </div>
    );
  }

  if (events.length === 0) {
    return (
      <div className="event-grid" style={{ gridTemplateColumns: `repeat(${columnCount}, 1fr)` }}>
        <div className="empty-state">
          <p className="empty-state__title">No events found</p>
          <p className="empty-state__description">Try adjusting your search or filters.</p>
        </div>
      </div>
    );
  }

  return (
    <div className="event-grid" style={{ gridTemplateColumns: `repeat(${columnCount}, 1fr)` }}>
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

export default EventGrid;