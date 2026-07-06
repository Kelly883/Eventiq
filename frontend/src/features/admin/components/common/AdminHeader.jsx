import React from 'react';

export const AdminHeader = ({ onSearch, onDateRangeChange }) => {
  return (
    <div className="flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between bg-white shadow rounded-lg p-4">
      <input
        className="border rounded px-3 py-2 text-sm flex-1"
        placeholder="Search..."
        onChange={(e) => onSearch?.(e.target.value)}
      />
      <div className="flex gap-2">
        <input
          type="date"
          className="border rounded px-3 py-2 text-sm"
          onChange={(e) => onDateRangeChange?.({ start: e.target.value })}
        />
        <input
          type="date"
          className="border rounded px-3 py-2 text-sm"
          onChange={(e) => onDateRangeChange?.({ end: e.target.value })}
        />
      </div>
    </div>
  );
};

