import React, { useState, useEffect, useCallback, useMemo } from 'react';
import LiveIndicator from '@/features/analytics/components/LiveIndicator';
import { ErrorBoundary } from '../ErrorBoundary';

interface DashboardHeaderProps {
  user?: { name?: string } | null;
  onFilterChange?: (filter: string) => void;
  onDateRangeChange?: (range: { from: Date; to: Date }) => void;
  onCreateEvent?: () => void;
}

const debounce = <T extends (...args: unknown[]) => void>(
  func: T,
  wait: number
): ((...args: Parameters<T>) => void) => {
  let timeout: ReturnType<typeof setTimeout> | null = null;
  return (...args: Parameters<T>) => {
    if (timeout) clearTimeout(timeout);
    timeout = setTimeout(() => func(...args), wait);
  };
};

const EVENT_FILTERS = [
  { value: 'all', label: 'All Events' },
  { value: 'concert', label: 'Concerts' },
  { value: 'summit', label: 'Summits' },
  { value: 'workshop', label: 'Workshops' },
] as const;

const DATE_RANGES = [
  { value: '', label: 'Date Range' },
  { value: '7d', label: 'Last 7 days' },
  { value: '30d', label: 'Last 30 days' },
  { value: '90d', label: 'Last 90 days' },
] as const;

const getGreeting = (): string => {
  const hour = new Date().getHours();
  if (hour < 12) return 'Good morning';
  if (hour < 18) return 'Good afternoon';
  return 'Good evening';
};

export const DashboardHeader = ({ 
  user, 
  onFilterChange, 
  onDateRangeChange, 
  onCreateEvent 
}: DashboardHeaderProps) => {
  const firstName = user?.name?.split(' ')[0] || 'there';
  const greeting = getGreeting();

  const [selectedFilter, setSelectedFilter] = useState('all');
  const [dateRange, setDateRange] = useState({
    from: new Date(new Date().setHours(0, 0, 0, 0)),
    to: new Date(),
  });

  const debouncedFilterChange = useMemo(
    () => debounce((filter: string) => {
      setSelectedFilter(filter);
      onFilterChange?.(filter);
    }, 300),
    [onFilterChange]
  ) as (filter: string) => void;

  const debouncedDateRangeChange = useMemo(
    () => debounce((range: { from: Date; to: Date }) => {
      setDateRange(range);
      onDateRangeChange?.(range);
    }, 300),
    [onDateRangeChange]
  ) as (range: { from: Date; to: Date }) => void;

  const handleCreateEvent = useCallback(() => {
    if (onCreateEvent) {
      onCreateEvent();
    } else {
      window.location.href = '/organizer/events/new';
    }
  }, [onCreateEvent]);

  const handleFilterChange = useCallback((e: React.ChangeEvent<HTMLSelectElement>) => {
    debouncedFilterChange(e.target.value);
  }, [debouncedFilterChange]);

  const handleDateRangeChange = useCallback((e: React.ChangeEvent<HTMLSelectElement>) => {
    const value = e.target.value;
    let from: Date;
    const to = new Date();
    
    switch (value) {
      case '7d':
        from = new Date();
        from.setDate(from.getDate() - 7);
        break;
      case '30d':
        from = new Date();
        from.setDate(from.getDate() - 30);
        break;
      case '90d':
        from = new Date();
        from.setDate(from.getDate() - 90);
        break;
      default:
        from = new Date(0);
    }
    
    from.setHours(0, 0, 0, 0);
    debouncedDateRangeChange({ from, to });
  }, [debouncedDateRangeChange]);

  return (
    <ErrorBoundary
      fallbackRender={(error: Error, reset: () => void) => (
        <div className="bg-white border border-rose-200 rounded-xl p-4" role="alert">
          <div className="flex items-center gap-3">
            <svg className="w-5 h-5 text-rose-500" fill="currentColor" viewBox="0 0 20 20">
              <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
            </svg>
            <div>
              <p className="text-sm text-rose-600">Header failed to load</p>
              <button onClick={reset} className="mt-2 text-xs text-rose-700 underline">Retry</button>
            </div>
          </div>
        </div>
      )}
    >
      <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-4 md:p-6">
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div className="min-w-0">
            <h2 className="text-xl md:text-2xl font-extrabold text-slate-900 truncate">
              {greeting}, {firstName}
            </h2>
            <p className="mt-1 text-sm text-slate-500 truncate">
              Manage your events, track sales, and grow your audience.
            </p>
          </div>

          <div className="flex flex-col sm:flex-row sm:items-center gap-3 w-full sm:w-auto">
            <LiveIndicator />

            <button
              onClick={handleCreateEvent}
              className="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
              aria-label="Create new event"
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
              </svg>
              Create Event
            </button>

            <div className="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
              <div className="relative flex-1 min-w-[140px]">
                <label htmlFor="header-filter" className="sr-only">Filter by event type</label>
                <select
                  id="header-filter"
                  value={selectedFilter}
                  onChange={handleFilterChange}
                  className="w-full appearance-none px-3 py-2 pr-10 border border-slate-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                >
                  {EVENT_FILTERS.map((item) => (
                    <option key={item.value} value={item.value}>{item.label}</option>
                  ))}
                </select>
                <svg className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
              </div>

              <div className="relative flex-1 min-w-[160px]">
                <label htmlFor="header-date-range" className="sr-only">Select date range</label>
                <select
                  id="header-date-range"
                  className="w-full appearance-none px-3 py-2 pr-10 border border-slate-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  onChange={handleDateRangeChange}
                >
                  {DATE_RANGES.map((item) => (
                    <option key={item.value} value={item.value}>{item.label}</option>
                  ))}
                </select>
                <svg className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" strokeWidth={2} viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </div>
          </div>
        </div>
      </div>
    </ErrorBoundary>
  );
};

export default DashboardHeader;