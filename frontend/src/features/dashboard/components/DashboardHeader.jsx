import React, { useCallback } from 'react';
import PropTypes from 'prop-types';
import { Plus, Filter, ChevronDown, ChevronUp, Dot } from 'lucide-react';

const RANGE_OPTIONS = [
  { value: 'today', label: 'Today' },
  { value: 'this-week', label: 'This Week' },
  { value: 'this-month', label: 'This Month' },
  { value: 'custom', label: 'Custom Range' },
];

export const DashboardHeader = ({
  onCreateEvent,
  onFilterChange,
  onDateRangeChange,
  filters = {},
  dateRange = 'this-month',
  title = 'Dashboard',
  subtitle,
}) => {
  const [isFilterOpen, setIsFilterOpen] = React.useState(false);

  const handleCreateEvent = useCallback((e) => {
    e.stopPropagation();
    onCreateEvent?.();
  }, [onCreateEvent]);

  const handleFilterChange = useCallback(
    (key, value) => {
      setIsFilterOpen(false);
      onFilterChange?.({ ...filters, [key]: value });
    },
    [onFilterChange, filters]
  );

  const handleDateRangeChange = useCallback((value) => {
    onDateRangeChange?.(value);
  }, [onDateRangeChange]);

  const handleToggleFilter = useCallback(() => {
    setIsFilterOpen((prev) => !prev);
  }, []);

  const handleFilterKeyDown = useCallback(
    (e) => {
      if (e.key === 'Escape') setIsFilterOpen(false);
    },
    []
  );

  const rangeLabel = RANGE_OPTIONS.find((o) => o.value === dateRange)?.label ?? dateRange;

  return (
    <div className="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
      <div>
        <h1 className="text-2xl font-bold text-slate-900 dark:text-slate-100 tracking-tight">{title}</h1>
        {subtitle && <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">{subtitle}</p>}
      </div>

      <div className="flex flex-col sm:flex-row sm:items-center gap-3 w-full sm:w-auto">
        <button
          onClick={handleCreateEvent}
          className="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-indigo-600 dark:bg-indigo-500 rounded-xl shadow-sm hover:bg-indigo-700 dark:hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all"
          aria-label="Create new event"
        >
          <Plus className="w-4 h-4" />
          Create New Event
        </button>

        <div className="relative group">
          <button
            onClick={handleToggleFilter}
            className="flex items-center gap-2 px-3 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-200 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-600 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
            aria-expanded={isFilterOpen}
            aria-haspopup="true"
          >
            <Filter className="w-4 h-4" />
            Filters
            {isFilterOpen ? (
              <ChevronUp className={`w-3 h-3 transition-transform`} />
            ) : (
              <ChevronDown className={`w-3 h-3 transition-transform`} />
            )}
          </button>

          {isFilterOpen && (
            <div
              className="absolute bottom-full left-0 z-20 mt-2 w-56 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-lg p-3 animate-in fade-in slide-in-from-bottom-2"
              onKeyDown={handleFilterKeyDown}
              role="menu"
            >
              <h3 className="mb-3 text-xs font-bold text-slate-400 dark:text-slate-300 uppercase tracking-wider">Filter by</h3>
              <div className="space-y-2">
                <label className="flex items-center gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={filters.status === 'active'}
                    onChange={(e) => handleFilterChange('status', e.target.checked ? 'active' : 'all')}
                    className="w-3 h-3 rounded text-indigo-600 dark:text-indigo-400 focus:ring-indigo-500"
                  />
                  Active events only
                </label>
                <label className="flex items-center gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={filters.status === 'cancelled'}
                    onChange={(e) => handleFilterChange('status', e.target.checked ? 'cancelled' : 'all')}
                    className="w-3 h-3 rounded text-indigo-600 dark:text-indigo-400 focus:ring-indigo-500"
                  />
                  Include cancelled
                </label>
                <label className="flex items-center gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={filters.live === true}
                    onChange={(e) => handleFilterChange('live', e.target.checked)}
                    className="w-3 h-3 rounded text-indigo-600 dark:text-indigo-400 focus:ring-indigo-500"
                  />
                  Live events only
                </label>
              </div>
            </div>
          )}
        </div>

        <div className="relative">
          <select
            value={dateRange}
            onChange={(e) => handleDateRangeChange(e.target.value)}
            className="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-200 text-xs font-medium px-3 py-2 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500"
            aria-label="Date range"
          >
            {RANGE_OPTIONS.map((opt) => (
              <option key={opt.value} value={opt.value}>
                {opt.label}
              </option>
            ))}
          </select>
          <ChevronDown className="w-3.5 h-3.5" />
        </div>

        <div className="flex items-center gap-2 px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm">
          <span className="h-2 w-2 rounded-full bg-green-500 animate-pulse" />
          <span className="font-medium text-slate-700 dark:text-slate-200">Live data</span>
        </div>
      </div>
    </div>
  );
};

DashboardHeader.displayName = 'DashboardHeader';

DashboardHeader.propTypes = {
  onCreateEvent: PropTypes.func,
  onFilterChange: PropTypes.func,
  onDateRangeChange: PropTypes.func,
  filters: PropTypes.shape({
    status: PropTypes.oneOf(['all', 'active', 'cancelled']),
    live: PropTypes.bool,
  }),
  dateRange: PropTypes.oneOf(['today', 'this-week', 'this-month', 'custom']),
  title: PropTypes.string,
  subtitle: PropTypes.string,
};

DashboardHeader.defaultProps = {
  onCreateEvent: undefined,
  onFilterChange: undefined,
  onDateRangeChange: undefined,
  filters: {},
  dateRange: 'this-month',
  title: 'Dashboard',
  subtitle: undefined,
};