import React, { useState, useCallback, useEffect, useRef } from 'react';
import { toast } from 'react-hot-toast';
import { useSalesActivity, useSalesActivitySSE, SalesActivityItem } from '../hooks/useDashboardMetrics';
import { ErrorBoundary } from './ErrorBoundary';

interface ActivityFilters {
  event?: string;
  tier?: string;
  dateFrom?: string;
  dateTo?: string;
}

const EVENT_OPTIONS = [
  { value: '', label: 'All Events' },
  { value: 'concert-2024', label: 'Concert Q4 2024' },
  { value: 'summit-2025', label: 'Summit 2025' },
  { value: 'workshop-series', label: 'Workshop Series' },
] as const;

const TIER_OPTIONS = [
  { value: '', label: 'All Tiers' },
  { value: 'general', label: 'General Admission' },
  { value: 'vip', label: 'VIP' },
  { value: 'premium', label: 'Premium' },
  { value: 'platinum', label: 'Platinum' },
] as const;

const SkeletonRow = () => (
  <div className="event-card mb-3 pb-3 border-b border-slate-100 animate-pulse">
    <div className="flex items-start gap-3">
      <div className="w-8 h-8 rounded-md bg-slate-200 flex-shrink-0" />
      <div className="flex-1 min-w-0 space-y-1">
        <div className="h-4 w-3/4 bg-slate-200 rounded" />
        <div className="h-3 w-1/2 bg-slate-200 rounded" />
      </div>
      <div className="w-20">
        <div className="h-4 w-full bg-slate-200 rounded" />
      </div>
      <div className="w-24">
        <div className="h-4 w-full bg-slate-200 rounded" />
      </div>
      <div className="w-24">
        <div className="h-3 w-full bg-slate-200 rounded" />
      </div>
    </div>
  </div>
);

const EmptyState = () => (
  <div className="text-center py-8 text-slate-500">
    <svg className="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
    </svg>
    <span className="block text-lg mb-2">No activity</span>
    <p>No sales activity matches the selected filters.</p>
  </div>
);

export const SalesActivityFeed = () => {
  const [filters, setFilters] = useState<ActivityFilters>({
    event: '',
    tier: '',
    dateFrom: '',
    dateTo: '',
  });
  const [isExporting, setIsExporting] = useState(false);
  const [lastRefresh, setLastRefresh] = useState<Date | null>(null);
  const activitiesRef = useRef<SalesActivityItem[]>([]);

  const { data: activities = [], isLoading, isFetching, error, refetch } = useSalesActivity(filters);

  const handleNewActivities = useCallback((newActivities: SalesActivityItem[]) => {
    activitiesRef.current = [...newActivities, ...activitiesRef.current]
      .filter((item, index, self) => 
        index === self.findIndex(t => t.id === item.id)
      )
      .slice(0, 50);
    setLastRefresh(new Date());
  }, []);

  useSalesActivitySSE(handleNewActivities);

  useEffect(() => {
    if (!isFetching && activities.length > 0) {
      activitiesRef.current = activities;
      setLastRefresh(new Date());
    }
  }, [activities, isFetching]);

  const filteredActivities = activitiesRef.current.filter((a) => {
    if (filters.event && (a.eventName || '').toLowerCase().indexOf(filters.event.toLowerCase()) === -1) return false;
    if (filters.tier && (a.tier || '').toLowerCase().indexOf(filters.tier.toLowerCase()) === -1) return false;
    if (filters.dateFrom) {
      const aDate = new Date(a.timestamp);
      const fromDate = new Date(filters.dateFrom);
      if (aDate < fromDate) return false;
    }
    if (filters.dateTo) {
      const aDate = new Date(a.timestamp);
      const toDate = new Date(filters.dateTo);
      if (aDate > toDate) return false;
    }
    return true;
  });

  const sortedActivities = [...filteredActivities].sort((a, b) => 
    new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime()
  );

  const handleFilterChange = useCallback((newFilters: Partial<ActivityFilters>) => {
    setFilters(prev => ({ ...prev, ...newFilters }));
  }, []);

  const exportToCSV = useCallback(() => {
    setIsExporting(true);
    const activitiesToExport = sortedActivities.slice(0, 1000);
    const headers = ['Timestamp', 'Event', 'Tier', 'Quantity', 'Price'];
    const rows = activitiesToExport.map((a) => [
      a.timestamp,
      a.eventName || '',
      a.tier || '',
      String(a.quantity),
      String(a.price),
    ]);
    const csv = [headers, ...rows].map(row =>
      row.map(cell => `"${String(cell).replace(/"/g, '""')}"`)
    ).join('\n');

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `activity-export-${new Date().toISOString().slice(0, 10)}.csv`;
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    setIsExporting(false);
    toast.success('Activity exported successfully');
  }, [sortedActivities]);

  const hasActiveFilters = Boolean(filters.event || filters.tier || filters.dateFrom || filters.dateTo);

  return (
    <ErrorBoundary
      fallbackRender={(error, reset) => (
        <div className="bg-white rounded-xl border border-rose-200 p-6" role="alert">
          <div className="flex items-center gap-3">
            <svg className="w-5 h-5 text-rose-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
            </svg>
            <div>
              <h3 className="text-lg font-semibold text-rose-800">Activity Feed Unavailable</h3>
              <p className="text-sm text-rose-600 mt-1">{error.message}</p>
              <button onClick={reset} className="mt-3 px-3 py-1.5 text-sm font-medium text-rose-700 bg-rose-100 border border-rose-200 rounded-lg hover:bg-rose-200">
                Retry
              </button>
            </div>
          </div>
        </div>
      )}
    >
      <div className="bg-white rounded-xl border border-slate-200 shadow-sm">
        <div className="px-4 py-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <h2 className="text-sm font-semibold text-slate-800" id="activity-feed-title">Sales Activity Feed</h2>
          <div className="flex flex-col sm:flex-row items-start sm:items-center gap-2 w-full sm:w-auto">
            <button
              type="button"
              onClick={exportToCSV}
              disabled={isExporting || sortedActivities.length === 0}
              className={`px-3 py-1.5 rounded-md text-xs font-medium transition-colors w-full sm:w-auto ${
                isExporting || sortedActivities.length === 0
                  ? 'opacity-50 cursor-not-allowed bg-slate-100 text-slate-400'
                  : 'text-indigo-600 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200'
              }`}
              aria-label={isExporting ? 'Exporting activity...' : 'Export Activity to CSV'}
            >
              {isExporting ? 'Exporting…' : 'Export Activity'}
            </button>
            {lastRefresh && (
              <span className="text-xs text-slate-400 sm:ml-2">
                Updated {Math.floor((Date.now() - lastRefresh.getTime()) / 1000)}s ago
                {isFetching && <span className="ml-1 text-indigo-500">⟳</span>}
              </span>
            )}
          </div>
        </div>

        <div className="px-4 py-3 border-b border-slate-100 bg-slate-50/50">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
              <label htmlFor="filter-event" className="block text-xs font-medium text-slate-600 mb-1">Event</label>
              <select
                id="filter-event"
                className="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                value={filters.event}
                onChange={(e) => handleFilterChange({ event: e.target.value })}
              >
                {EVENT_OPTIONS.map((opt) => (
                  <option key={opt.value} value={opt.value}>{opt.label}</option>
                ))}
              </select>
            </div>
            <div>
              <label htmlFor="filter-tier" className="block text-xs font-medium text-slate-600 mb-1">Tier</label>
              <select
                id="filter-tier"
                className="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                value={filters.tier}
                onChange={(e) => handleFilterChange({ tier: e.target.value })}
              >
                {TIER_OPTIONS.map((opt) => (
                  <option key={opt.value} value={opt.value}>{opt.label}</option>
                ))}
              </select>
            </div>
            <div>
              <label htmlFor="filter-date-from" className="block text-xs font-medium text-slate-600 mb-1">Date From</label>
              <input
                id="filter-date-from"
                type="date"
                className="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                value={filters.dateFrom}
                onChange={(e) => handleFilterChange({ dateFrom: e.target.value })}
              />
            </div>
            <div>
              <label htmlFor="filter-date-to" className="block text-xs font-medium text-slate-600 mb-1">Date To</label>
              <input
                id="filter-date-to"
                type="date"
                className="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                value={filters.dateTo}
                onChange={(e) => handleFilterChange({ dateTo: e.target.value })}
              />
            </div>
          </div>
          {hasActiveFilters && (
            <button
              type="button"
              onClick={() => setFilters({ event: '', tier: '', dateFrom: '', dateTo: '' })}
              className="mt-2 text-xs text-indigo-600 hover:text-indigo-800 font-medium"
            >
              Clear all filters
            </button>
          )}
        </div>

        <div className="px-4 py-4 max-h-96 overflow-y-auto">
          {isLoading ? (
            Array.from({ length: 5 }).map((_, i) => <SkeletonRow key={i} />)
          ) : sortedActivities.length === 0 ? (
            <EmptyState />
          ) : (
            sortedActivities.map((activity, index) => (
              <div
                key={activity.id}
                className="event-card mb-3 pb-3 border-b border-slate-100 last:border-0"
                style={{ animationDelay: `${index * 0.03}s` }}
              >
                <div className="flex items-start gap-3">
                  <div className="w-8 h-8 rounded-md flex items-center justify-center flex-shrink-0 bg-indigo-100 text-indigo-600">
                    <span className="text-sm font-medium">{activity.eventInitials || 'E'}</span>
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium text-slate-800 truncate">{activity.eventName || 'Unknown Event'}</p>
                    <p className="text-xs text-slate-500 truncate">{activity.tier || 'All Tiers'}</p>
                  </div>
                  <div className="w-20 text-right">
                    <p className="text-sm font-medium text-slate-700">{activity.quantity > 0 ? `${activity.quantity} tickets` : '0 tickets'}</p>
                  </div>
                  <div className="w-28 text-right">
                    <p className="text-sm font-medium text-slate-700">₦{activity.price?.toLocaleString() || '0'}</p>
                  </div>
                  <p className="text-xs text-slate-400 ml-auto whitespace-nowrap">{activity.timestamp || ''}</p>
                </div>
              </div>
            ))
          )}
        </div>
      </div>
    </ErrorBoundary>
  );
};

export default SalesActivityFeed;