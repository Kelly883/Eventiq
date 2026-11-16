import React, { useState, useMemo, useCallback } from 'react';
import { Calendar } from 'react-big-calendar';
import 'react-big-calendar/lib/css/react-big-calendar.css';
import { calendarLocalizer } from '../../utils/calendarLocalizer';
import { CALENDAR_VIEWS, CalendarView } from '../../utils/dateConstants';
import ViewToggle from '../filters/ViewToggle';
import AvailabilityBadge from '../events/AvailabilityBadge';

/**
 * Shape react-big-calendar expects for each event. Kept separate from the
 * backend `Event` type so CalendarGrid stays decoupled from the API contract.
 */
export interface CalendarEvent {
  id: number | string;
  title: string;
  start: Date;
  end: Date;
  ticketTiers?: ReadonlyArray<{
    totalTickets?: number;
    soldCount?: number;
  }>;
}

export type AvailabilityColor = 'available' | 'low' | 'sold_out';

const AVAILABILITY_COLORS: Record<AvailabilityColor, string> = {
  available: '#10b981', // emerald-500 - also has pattern overlay for color-blind support
  low: '#f59e0b', // amber-500 - also has pattern overlay for color-blind support
  sold_out: '#ef4444', // red-500 - also has pattern overlay for color-blind support
};

/** Color-blind friendly SVG patterns as secondary differentiation */
const AVAILABILITY_PATTERNS = {
  available: 'M6 2l3 3L11 6L8 10L6 8L2 12L6 6zM6 2l3 3L11 6L8 10L6 8L2 12L6 6z',
  low: 'M6 2l3 3L11 6L8 10L6 8L2 12L6 6z',
  sold_out: 'M2 12l7-7 2 5-7 2-5-2zM6 2l3 3L11 6L8 10L6 8L2 12L6 6z',
};

/** Data URLs for pattern backgrounds (for color-blind users) */
const PATTERN_IMAGES = {
  available:
    'data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' fill=\'none%3E%3Cpath d=\'M6 2l3 3L11 6L8 10L6 8L2 12L6 6zM6 2l3 3L11 6L8 10L6 8L2 12L6 6z\' fill=\'%2310b981\'/%3E%3C/svg%3E',
  low:
    'data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' fill=\'none%3E%3Cpath d=\'M6 2l3 3L11 6L8 10L6 8L2 12L6 6z\' fill=\'%23f59e0b\'/%3E%3C/svg%3E',
  sold_out:
    'data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' fill=\'none%3E%3Cpath d=\'M6 2l3 3L11 6L8 10L6 8L2 12L6 6z\' fill=\'%23ef4444\'/%3E%3C/svg%3E',
};

type CalendarGridProps = {
  events?: CalendarEvent[];
  isLoading?: boolean;
  onDateSelect?: (date: string) => void;
  view?: CalendarView;
  onViewChange?: (view: CalendarView) => void;
  filters?: Record<string, unknown>;
};

const CalendarGrid = ({
  events = [],
  isLoading = false,
  onDateSelect,
  view = CALENDAR_VIEWS.MONTH,
  onViewChange,
  filters,
}: CalendarGridProps) => {
  const [currentView, setCurrentView] = useState(view);
  const [isLoading, setIsLoading] = useState(false);

  const handleDateSelect = React.useCallback(
    (startDate: Date) => {
      if (onDateSelect) {
        const formattedDate = startDate.toISOString().split('T')[0];
        onDateSelect(formattedDate);
      }
    },
    [onDateSelect]
  );

  const handleViewChange = React.useCallback(
    (newView: CalendarView) => {
      setCurrentView(newView);
      onViewChange?.(newView);
    },
    [onViewChange]
  );

  // Performance note: For calendars with many events per day (>50), consider
  // using react-window or react-virtualized for the event list renderer.
  // The current implementation groups events by date for O(1) lookup but
  // may need virtualization for dense schedules.

  const handleSlotSelect = React.useCallback(
    (slotInfo: { start: Date; end: Date; action: string }) => {
      if (slotInfo && slotInfo.start) {
        handleDateSelect(slotInfo.start);
      }
    },
    [handleDateSelect]
  );

  // Group events by date for O(1) lookup in getDayProps (memoized)
  const eventsByDate = useMemo(() => {
    const map: Record<string, CalendarEvent[]> = {};
    events.forEach((event) => {
      const start = new Date(event.start);
      const dateKey = start.toISOString().split('T')[0];
      if (!map[dateKey]) map[dateKey] = [];
      map[dateKey].push(event);
    });
    return map;
  }, [events]);

  const getDayProps = React.useCallback(
    (date: Date, view: string) => {
      const dateKey = date.toISOString().split('T')[0];
      const dayEvents: CalendarEvent[] = eventsByDate[dateKey] || [];
      const eventCount = dayEvents.length;

      let availabilityColor: AvailabilityColor = 'available';
      if (dayEvents.length > 0) {
        const tier = dayEvents[0]?.ticketTiers?.[0];
        if (tier) {
          const total = tier.totalTickets ?? 1;
          const sold = tier.soldCount ?? 0;
          const remaining = total - sold;
          if (remaining <= 0) availabilityColor = 'sold_out';
          else if (remaining / total <= 0.2) availabilityColor = 'low';
          else availabilityColor = 'available';
        }
      }

      const colorStyle: Record<AvailabilityColor, React.CSSProperties> = {
        available: { 
          borderLeft: `3px solid ${AVAILABILITY_COLORS.available}`,
          backgroundImage: AVAILABILITY_PATTERNS.available,
          backgroundRepeat: 'no-repeat',
          backgroundSize: 'contain',
        },
        low: { 
          borderLeft: `3px solid ${AVAILABILITY_COLORS.low}`,
          backgroundImage: AVAILABILITY_PATTERNS.low,
          backgroundRepeat: 'no-repeat',
          backgroundSize: 'contain',
        },
        sold_out: { 
          borderLeft: `3px solid ${AVAILABILITY_COLORS.sold_out}`,
          backgroundImage: AVAILABILITY_PATTERNS.sold_out,
          backgroundRepeat: 'no-repeat',
          backgroundSize: 'contain',
        },
      };

      return {
        style: {
          border: 'none',
          padding: '4px 0',
          margin: '0 2px',
          borderRadius: '0',
          ...colorStyle[availabilityColor],
        },
        className: `relative${eventCount > 0 ? ' p-1' : ''}`,
        'aria-label': eventCount > 0
          ? `${eventCount} event${eventCount !== 1 ? 's' : ''} on ${dateKey}`
          : `No events on ${dateKey}`,
        role: 'gridcell',
        tabIndex: 0,
        onKeyDown: (e: React.KeyboardEvent) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            handleDateSelect(date);
          }
        },
      };
    },
    [eventsByDate, handleDateSelect]
  );

  const getEventProps = React.useCallback(
    (event: CalendarEvent, start: Date, end: Date) => {
      const tier = event.ticketTiers?.[0];
      if (!tier) return {};

      const total = tier.totalTickets ?? 1;
      const sold = tier.soldCount ?? 0;
      const remaining = total - sold;

      if (remaining <= 0) {
        return {
          style: {
            color: '#dc2626',
            textDecoration: 'line-through',
          },
        };
      } else if (remaining / total <= 0.2) {
        return {
          style: {
            color: '#d97706',
          },
        };
      }
      return {};
    },
    []
  );

  return (
    <div>
      {isLoading && (<>
        <div className="skeleton-grid animate-pulse" />
      </>)
      {events.length === 0 && (
        <div
          className="empty-state text-center py-12"
          aria-label="No events found"
        >
          <svg
            className="w-12 h-12 mx-auto text-gray-400 mb-3 opacity-50"
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden="true"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={1.5}
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
            />
          </svg>
          <h3 className="text-xl font-semibold mb-2">No events</h3>
          <p className="text-gray-600 mb-6">
            No events match your selected filters. Try adjusting your criteria or clearing filters.
          </p>
          <button
            onClick={() => window.history.back()}
            className="inline-flex items-center gap-2 rounded border px-4 py-2 text-sm font-medium text-indigo-600 hover:text-indigo-800 transition-colors"
            aria-label="Go back to calendar view"
          >
            Adjust Filters
            <svg className="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
              <path d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>
      )}
      <ViewToggle
        currentView={currentView}
        onViewChange={handleViewChange}
      />
      <div
        style={{ height: 600, marginTop: '16px' }}
        role="region"
        aria-label="Event calendar"
      >
        <Calendar
          localizer={calendarLocalizer}
          events={events}
          startAccessor="start"
          endAccessor="end"
          view={currentView}
          onView={(view) => setCurrentView(view as CalendarView)}
          views={[CALENDAR_VIEWS.MONTH, CALENDAR_VIEWS.WEEK, CALENDAR_VIEWS.DAY]}
          onSelectEvent={(event) => {
            handleDateSelect(event.start);
          }}
          onSelectSlot={handleSlotSelect}
          dayProp={getDayProps}
          eventProp={getEventProps}
          style={{ height: '100%' }}
        />
      </div>
    </div>
  );
};

export default CalendarGrid;