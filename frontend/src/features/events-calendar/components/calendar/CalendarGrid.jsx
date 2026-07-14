import React, { useState } from 'react';
import { Calendar } from 'react-big-calendar';
import 'react-big-calendar/lib/css/react-big-calendar.css';
import { calendarLocalizer } from '../../utils/calendarLocalizer';
import { CALENDAR_VIEWS } from '../../utils/dateConstants';

/**
 * Month/week/day event calendar. Expects `events` as
 * [{ title, start: Date, end: Date, resource? }, ...].
 */
const CalendarGrid = ({ events = [], onSelectEvent, defaultView = CALENDAR_VIEWS.MONTH }) => {
  const [view, setView] = useState(defaultView);

  return (
    <div
      style={{ height: 600 }}
      role="region"
      aria-label="Event calendar"
    >
      <Calendar
        localizer={calendarLocalizer}
        events={events}
        startAccessor="start"
        endAccessor="end"
        // react-big-calendar renders event.title as visible text inside
        // each event cell, which already serves as its accessible name
        // for screen readers - no extra aria wiring needed for that.
        // tooltipAccessor controls the native title="" hover tooltip;
        // defaults to the event title if not set.
        view={view}
        onView={setView}
        views={[CALENDAR_VIEWS.MONTH, CALENDAR_VIEWS.WEEK, CALENDAR_VIEWS.DAY]}
        onSelectEvent={onSelectEvent}
        style={{ height: '100%' }}
      />
    </div>
  );
};

export default CalendarGrid;
