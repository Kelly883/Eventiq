import React, { useState, useMemo, useCallback } from 'react';
import { Calendar } from 'react-big-calendar';
import 'react-big-calendar/lib/css/react-big-calendar.css';
import { calendarLocalizer } from '../../utils/calendarLocalizer';
import { CALENDAR_VIEWS, CalendarView } from '../../utils/dateConstants';
import ViewToggle from '../filters/ViewToggle';
import AvailabilityIndicator from '../events/AvailabilityIndicator';

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
  available: '#10b981', // emerald-500
  low: '#f59e0b', // amber-500
  sold_out: '#ef4444', // red-500
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
        available: { borderLeft: `3px solid ${AVAILABILITY_COLORS.available}` },
        low: { borderLeft: `3px solid ${AVAILABILITY_COLORS.low}` },
        sold_out: { borderLeft: `3px solid ${AVAILABILITY_COLORS.sold_out}` },
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