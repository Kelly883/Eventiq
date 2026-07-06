import React from 'react';

const FilterPanel = ({ filters, onFiltersChange }) => {
  return (
    <div className="bg-white shadow rounded-lg p-4">
      <div className="text-sm font-semibold text-gray-900">Filters</div>
      <div className="mt-3 flex gap-3 flex-wrap">
        <input
          className="border rounded px-3 py-2 text-sm"
          placeholder="Search action/entity..."
          value={filters?.query ?? ''}
          onChange={(e) => onFiltersChange?.({ ...filters, query: e.target.value })}
        />
        <input
          type="date"
          className="border rounded px-3 py-2 text-sm"
          onChange={(e) => onFiltersChange?.({ ...filters, start: e.target.value })}
        />
        <input
          type="date"
          className="border rounded px-3 py-2 text-sm"
          onChange={(e) => onFiltersChange?.({ ...filters, end: e.target.value })}
        />
      </div>
    </div>
  );
};

export { FilterPanel };

