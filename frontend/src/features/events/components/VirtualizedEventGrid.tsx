import React, { useRef, useState, useEffect, useMemo, useCallback } from 'react';
import EventCard from './EventCard';
import type { Event } from '../types/shared';

interface VirtualizedEventGridProps {
  events: Event[];
  isLoading: boolean;
  hasMore: boolean;
  onLoadMore?: () => void;
  onEventClick?: (event: Event) => void;
  itemHeight?: number;
  overscan?: number;
  className?: string;
}

const getColumnCount = (width: number): number => {
  if (width >= 1024) return 3;
  if (width >= 768) return 2;
  return 1;
};

export const VirtualizedEventGrid = ({
  events,
  isLoading,
  hasMore,
  onLoadMore,
  onEventClick,
  itemHeight = 400,
  overscan = 2,
  className = '',
}: VirtualizedEventGridProps) => {
  const [containerWidth, setContainerWidth] = useState(0);
  const [scrollTop, setScrollTop] = useState(0);
  const containerRef = useRef<HTMLDivElement>(null);
  const observerRef = useRef<IntersectionObserver | null>(null);

  const columnCount = useMemo(() => getColumnCount(containerWidth || (typeof window !== 'undefined' ? window.innerWidth : 1024)), [containerWidth]);
  const rowHeight = itemHeight;
  const totalRows = Math.ceil(events.length / columnCount);

  // Update container width on resize
  useEffect(() => {
    const updateWidth = () => {
      if (containerRef.current) {
        setContainerWidth(containerRef.current.clientWidth);
      }
    };
    updateWidth();
    window.addEventListener('resize', updateWidth);
    return () => window.removeEventListener('resize', updateWidth);
  }, []);

  // Handle scroll for infinite loading
  useEffect(() => {
    const container = containerRef.current;
    if (!container) return;

    const handleScroll = () => {
      setScrollTop(container.scrollTop);
      
      // Check if we need to load more
      if (hasMore && onLoadMore && !isLoading) {
        const scrollHeight = container.scrollHeight;
        const clientHeight = container.clientHeight;
        const scrollTop = container.scrollTop;
        
        // Trigger load more when within 2 rows of bottom
        const threshold = rowHeight * 2;
        if (scrollHeight - scrollTop - clientHeight < threshold) {
          onLoadMore();
        }
      }
    };

    container.addEventListener('scroll', handleScroll, { passive: true });
    return () => container.removeEventListener('scroll', handleScroll);
  }, [hasMore, onLoadMore, isLoading, rowHeight]);

  // Calculate visible rows
  const visibleRange = useMemo(() => {
    const startRow = Math.max(0, Math.floor(scrollTop / rowHeight) - overscan);
    const endRow = Math.min(totalRows - 1, Math.ceil((scrollTop + (containerWidth ? containerWidth : 1024)) / rowHeight) + overscan);
    return { startRow, endRow };
  }, [scrollTop, rowHeight, totalRows, overscan, containerWidth]);

  // Get visible events
  const visibleEvents = useMemo(() => {
    const startIdx = visibleRange.startRow * columnCount;
    const endIdx = Math.min(events.length, (visibleRange.endRow + 1) * columnCount);
    return events.slice(startIdx, endIdx);
  }, [events, visibleRange, columnCount]);

  // Render placeholder for non-visible items
  const placeholderHeight = totalRows * rowHeight;
  const offsetY = visibleRange.startRow * rowHeight;

  const handleScroll = useCallback((e: React.UIEvent<HTMLDivElement>) => {
    setScrollTop(e.currentTarget.scrollTop);
  }, []);

  if (isLoading) {
    return (
      <div
        ref={containerRef}
        className={`event-grid ${className}`}
        style={{ gridTemplateColumns: `repeat(${columnCount}, 1fr)` }}
        aria-label="Loading events"
        onScroll={handleScroll}
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
        ref={containerRef}
        className={`event-grid ${className}`}
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
      ref={containerRef}
      className={`event-grid ${className}`}
      style={{ gridTemplateColumns: `repeat(${columnCount}, 1fr)` }}
      onScroll={handleScroll}
    >
      <div
        style={{
          height: placeholderHeight,
          position: 'relative',
        }}
      >
        <div
          style={{
            transform: `translateY(${offsetY}px)`,
          }}
        >
          {visibleEvents.map((event) => (
            <EventCard
              key={event.id}
              event={event}
              onClick={() => onEventClick?.(event)}
            />
          ))}
        </div>
      </div>
    </div>
  );
};

export default React.memo(VirtualizedEventGrid);