import React from 'react';

const CalendarDayDetailModal = ({ selectedDate, events }) => {
  if (!selectedDate) {
    return null;
  }
  const dateEvents = events || [];
  return (
    <div>
      <h3>Events on {selectedDate}</h3>
      {dateEvents.length > 0 ? (
        <React.Fragment>
          {dateEvents.map((event, i) => (
            <span key={i}>{event.name || event.title}</span>
          ))}
        </React.Fragment>
      ) : (
        <p>No events scheduled for this date.</p>
      )}
    </div>
  );
};

export default CalendarDayDetailModal;