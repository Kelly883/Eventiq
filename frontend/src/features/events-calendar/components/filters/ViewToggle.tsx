import React from 'react';
import type { CalendarView } from '../../utils/dateConstants';

const VIEW_OPTIONS: { value: CalendarView; label: string }[] = [
  { value: 'month', label: 'Month' },
  { value: 'week', label: 'Week' },
  { value: 'day', label: 'Day' },
];

type ViewToggleProps = {
  currentView: CalendarView;
  onViewChange: (view: CalendarView) => void;
};

const ViewToggle = ({ currentView, onViewChange }: ViewToggleProps) => {
  const [focusedIndex, setFocusedIndex] = React.useState(
    VIEW_OPTIONS.findIndex((o) => o.value === currentView)
  );

  React.useEffect(() => {
    setFocusedIndex(VIEW_OPTIONS.findIndex((o) => o.value === currentView));
  }, [currentView]);

  const handleKeyDown = (
    e: React.KeyboardEvent<HTMLButtonElement>,
    index: number
  ) => {
    if (e.key === 'ArrowRight') {
      e.preventDefault();
      const next = (index + 1) % VIEW_OPTIONS.length;
      setFocusedIndex(next);
      document
        .getElementById(`view-toggle-${VIEW_OPTIONS[next].value}`)
        ?.focus();
    } else if (e.key === 'ArrowLeft') {
      e.preventDefault();
      const prev = (index - 1 + VIEW_OPTIONS.length) % VIEW_OPTIONS.length;
      setFocusedIndex(prev);
      document
        .getElementById(`view-toggle-${VIEW_OPTIONS[prev].value}`)
        ?.focus();
    } else if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      onViewChange(VIEW_OPTIONS[index].value);
    }
  };

  return (
    <div
      className="flex gap-1 mb-4"
      role="radiogroup"
      aria-label="Calendar view"
    >
      {VIEW_OPTIONS.map((option, index) => (
        <button
          key={option.value}
          type="button"
          role="radio"
          aria-checked={currentView === option.value}
          aria-pressed={currentView === option.value}
          id={`view-toggle-${option.value}`}
          tabIndex={focusedIndex === index ? 0 : -1}
          onClick={() => onViewChange(option.value)}
          onKeyDown={(e) => handleKeyDown(e, index)}
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
