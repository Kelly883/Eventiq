import React from 'react';

export const EventDetailsPanel = ({ event, loading }) => {
  if (loading) return <div className="animate-pulse h-40 bg-gray-100 rounded" />;
  if (!event) return <div className="text-sm text-gray-500">Select an event</div>;

  return (
    <div className="p-4 border rounded">
      <div className="font-semibold text-gray-900">{event.title ?? 'Event'}</div>
      <div className="text-sm text-gray-600 mt-1">Status: {event.status ?? '—'}</div>
    </div>
  );
};

