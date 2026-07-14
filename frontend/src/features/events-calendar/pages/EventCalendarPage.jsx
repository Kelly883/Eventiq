import React from 'react';
import CalendarGrid from '../components/calendar/CalendarGrid';

const EventCalendarPage = () => {
  // TODO: replace with real events from useCalendarEvents() once the
  // backend calendar endpoint exists.
  const events = [];

  return (
    <div className="p-6">
      <h1 className="text-xl font-bold mb-4">Event Calendar</h1>
      <CalendarGrid events={events} />
    </div>
  );
};

export default EventCalendarPage;
