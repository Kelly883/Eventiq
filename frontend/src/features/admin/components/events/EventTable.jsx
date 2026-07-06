import React from 'react';

export const EventTable = ({ loading, events, onRowClick }) => {
  if (loading) return <div className="animate-pulse h-48 bg-gray-100 rounded" />;
  return (
    <table className="w-full text-sm">
      <thead>
        <tr className="text-left text-gray-500 border-b">
          <th className="py-2 px-2">Event</th>
          <th className="py-2 px-2">Status</th>
        </tr>
      </thead>
      <tbody>
        {(events || []).length === 0 ? (
          <tr>
            <td colSpan={2} className="py-6 px-2 text-gray-500">
              No events
            </td>
          </tr>
        ) : (
          events.map((ev) => (
            <tr
              key={ev.id}
              className="border-b hover:bg-gray-50 cursor-pointer"
              onClick={() => onRowClick?.(ev)}
            >
              <td className="py-2 px-2 font-medium text-gray-900">{ev.title ?? '—'}</td>
              <td className="py-2 px-2 text-gray-600">{ev.status ?? '—'}</td>
            </tr>
          ))
        )}
      </tbody>
    </table>
  );
};

