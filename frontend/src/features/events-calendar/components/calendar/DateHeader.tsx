import React from 'react';
import { formatEventDate, Availability } from '../../types';

const DateHeader = ({ date, eventCount }: { date: string; eventCount: number }) => {
  const formatted = formatEventDate(date);

  return (
    <div className="flex items-center gap-3">
      <span className="text-sm font-medium">
        {formatted}
      </span>
      <span className="text-sm text-gray-500">
        {eventCount} {eventCount !== 1 ? 'events' : 'event'}
      </span>
    </div>
  );
};

export default DateHeader;