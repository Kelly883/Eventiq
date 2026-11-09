import React from 'react';

const VIEW_OPTIONS = [
  { value: 'month', label: 'Month' },
  { value: 'week', label: 'Week' },
  { value: 'day', label: 'Day' },
];

const ViewToggle = ({
  currentView,
  onViewChange,
}) => {
  return (
    <div
      className="flex gap-1 mb-4"
      role="radiogroup"
      aria-label="Calendar view"
    >
      {VIEW_OPTIONS.map((option) => (
        <button
          key={option.value}
          type="button"
          role="radio"
          aria-checked={currentView === option.value}
          onClick={() => onViewChange?.(option.value)}
          className={`px-3 py-1.5 rounded-md border text-sm font-medium transition-colors ${
            currentView === option.value
              ? 'bg-primary-600 text-white border-primary-600 shadow-sm'
              : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 hover:border-gray-400'
          }`}
        >
          {option.label}
        </button>
      ))}
    </div>
  );
};

export default ViewToggle;
