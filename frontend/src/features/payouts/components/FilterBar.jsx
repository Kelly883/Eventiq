import React from 'react';
import { getDateRangeOptions } from '../utils';

const FilterBar = ({ filters, onFiltersChange }) => {
  const dateRangeOptions = getDateRangeOptions();

  return (
    <div className="flex flex-wrap gap-4 p-4 bg-white border rounded-lg">
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
        <select
          value={filters.status || ''}
          onChange={(e) => onFiltersChange({ ...filters, status: e.target.value })}
          className="px-3 py-2 border rounded-md"
        >
          <option value="">All Statuses</option>
          <option value="pending">Pending</option>
          <option value="processing">Processing</option>
          <option value="completed">Completed</option>
          <option value="failed">Failed</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>

      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
        <select
          value={filters.dateRange || ''}
          onChange={(e) => onFiltersChange({ ...filters, dateRange: e.target.value })}
          className="px-3 py-2 border rounded-md"
        >
          <option value="">Select Range</option>
          {dateRangeOptions.map((option) => (
            <option key={option.value} value={option.value}>{option.label}</option>
          ))}
        </select>
      </div>

      <div className="flex items-end">
        <button
          onClick={() => onFiltersChange({})}
          className="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
        >
          Reset
        </button>
      </div>
    </div>
  );
};

export default FilterBar;
