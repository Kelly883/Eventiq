import React from 'react';

export const EventFilters = ({ filters, onFiltersChange }) => {
  return (
    <div className="bg-white shadow rounded-lg p-4">
      <div className="text-sm font-semibold text-gray-900">Event Filters</div>
      <div className="mt-3 flex gap-3 flex-wrap">
        <input
          className="border rounded px-3 py-2 text-sm"
          placeholder="Search events..."
          value={filters?.query ?? ''}
          onChange={(e) => onFiltersChange?.({ ...filters, query: e.target.value })}
        />
      </div>
    </div>
  );
};

