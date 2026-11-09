import React, { useState, useMemo } from 'react';
import { Calendar } from 'react-big-calendar';
import 'react-big-calendar/lib/css/react-big-calendar.css';
import { calendarLocalizer } from '../../utils/calendarLocalizer';
import { CALENDAR_VIEWS } from '../../utils/dateConstants';
import ViewToggle from '../filters/ViewToggle';
import AvailabilityIndicator from '../events/AvailabilityIndicator';

/**
 * Main calendar view powered by react-big-calendar.
 * Expects `events` as array of Event objects with start/end dates.
 * Color-coded event indicators show availability (green/amber/red).
 * Shows event count on each date.
 * Includes month/week/day view toggle buttons at the top.
 */
const CalendarGrid = ({
  events = [],
  onDateSelect,
  view = CALENDAR_VIEWS.MONTH,
  onViewChange,
  filters,
}) => {
  const [currentView, setCurrentView] = useState(view);

  const handleViewChange = (newView) => {
    setCurrentView(newView);
    onViewChange?.(newView);
  };

  const handleDateSelect = (startDate) => {
    if (onDateSelect) {
      const formattedDate = startDate.toISOString().split('T')[0];
      onDateSelect(formattedDate);
    }
  };

  // Group events by date for O(1) lookup in getDayProps (memoized)
  const eventsByDate = useMemo(() => {
    const map = {};
    events.forEach((event) => {
      const start = new Date(event.start);
      const dateKey = start.toISOString().split('T')[0];
      if (!map[dateKey]) map[dateKey] = [];
      map[dateKey].push(event);
    });
    return map;
  }, [events]);

  const getDayProps = (date, view) => {
    const dateKey = date.toISOString().split('T')[0];
    const dayEvents = eventsByDate[dateKey] || [];
    const eventCount = dayEvents.length;

    let availabilityColor = 'available';
    if (dayEvents.length > 0) {
      const firstEvent = dayEvents[0];
      const tier = firstEvent.ticketTiers?.[0];
      if (tier) {
        const total = tier.totalTickets || 1;
        const sold = tier.soldCount || 0;
        const remaining = total - sold;
        if (remaining <= 0) availabilityColor = 'sold_out';
        else if (remaining / total <= 0.2) availabilityColor = 'low';
        else availabilityColor = 'available';
      }
    }

    const colorStyle = {
      available: { borderLeft: '3px solid #10b981' },
      low: { borderLeft: '3px solid #f59e0b' },
      sold_out: { borderLeft: '3px solid #ef4444' },
    };

    return {
      style: {
        border: 'none',
        padding: '4px 0',
        margin: '0 2px',
        borderRadius: '0',
      },
      className: `relative${eventCount > 0 ? ' p-1' : ''}`,
      'aria-label': eventCount > 0
        ? `${eventCount} event${eventCount !== 1 ? 's' : ''} on ${dateKey}`
        : `No events on ${dateKey}`,
    };
  };

  const getEventProps = (event, start, end) => {
    const tier = event.ticketTiers?.[0];
    if (!tier) return {};

    const total = tier.totalTickets || 1;
    const sold = tier.soldCount || 0;
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
  };

  return (
    <div>
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
          onView={setCurrentView}
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