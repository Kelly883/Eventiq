import React, { useState, useCallback, useEffect, useMemo, useRef } from 'react';
import toast from 'react-hot-toast';
import { formatDistanceToNow } from 'date-fns';
import { List } from 'react-window';
import { useSalesActivity, useSalesActivitySSE, SalesActivityItem } from '../hooks/useDashboardMetrics';
import { ErrorBoundary } from './ErrorBoundary';
import { useSearchParams } from 'react-router-dom';
import { useNavigate } from 'react-router-dom';

interface ActivityFilters {
  event?: string;
  tier?: string;
  dateFrom?: string;
  dateTo?: string;
}

const FILTER_KEYS: Array<keyof ActivityFilters> = ['event', 'tier', 'dateFrom', 'dateTo'];

const SkeletonRow = () => (
  <li className="py-3 border-b border-slate-100 animate-pulse" aria-hidden="true">
    <div className="flex items-start gap-3">
      <div className="w-8 h-8 rounded-md bg-slate-200 flex-shrink-0" />
      <div className="flex-1 min-w-0 space-y-1.5">
        <div className="h-4 w-3/4 bg-slate-200 rounded" />
        <div className="h-3 w-1/3 bg-slate-200 rounded" />
      </div>
      <div className="hidden sm:block w-16 h-4 bg-slate-200 rounded" />
      <div className="hidden sm:block w-20 h-4 bg-slate-200 rounded" />
      <div className="hidden sm:block w-16 h-4 bg-slate-200 rounded" />
    </div>
  </li>
);

const EmptyState = ({ hasFilters, onClearFilters }: { hasFilters: boolean; onClearFilters: () => void }) => (
  <li role="status" className="text-center py-10 text-slate-500">
    <svg className="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
    </svg>
    <span className="block text-base font-semibold text-slate-700 mb-1">
      {hasFilters ? 'No matching activity' : 'No sales activity yet'}
    </span>
    <p className="text-sm">
      {hasFilters
        ? 'No sales match the selected filters.'
        : 'New ticket sales will appear here in real time.'}
    </p>
    {hasFilters && (
      <button
        type="button"
        onClick={onClearFilters}
        className="mt-3 text-xs font-semibold text-indigo-600 hover:text-indigo-800"
      >
        Clear all filters
      </button>
    )}
  </li>
);

const ErrorState = ({ onRetry }: { onRetry: () => void }) => (
  <li role="alert" className="py-8 text-center">
    <svg className="w-10 h-10 mx-auto text-rose-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
    </svg>
    <p className="text-base font-semibold text-slate-700 mb-1">Activity feed unavailable</p>
    <p className="text-sm text-slate-500 mb-3">We couldn&apos;t load recent sales right now.</p>
    <button
      type="button"
      onClick={onRetry}
      className="px-3 py-1.5 text-xs font-semibold text-rose-700 bg-rose-50 border border-rose-200 rounded-lg hover:bg-rose-100"
    >
      Retry
    </button>
  </li>
);

/** Ticking relative timestamp, isolated so the whole feed doesn't re-render. */
const TimeAgo = ({ value, intervalMs = 30_000 }: { value: string; intervalMs?: number }) => {
  const [, setTick] = useState(0);
  useEffect(() => {
    const id = setInterval(() => setTick((t) => t + 1), intervalMs);
    return () => clearInterval(id);
  }, [intervalMs]);

  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) {
    return <time dateTime={value}>{value}</time>;
  }
  return (
    <time dateTime={parsed.toISOString()} title={parsed.toLocaleString()}>
      {formatDistanceToNow(parsed, { addSuffix: true })}
    </time>
  );
};

/** Seconds-since-refresh label; ticks alone at 1s so only this span re-renders. */
const RefreshedAgo = ({ at }: { at: Date }) => {
  const [, setTick] = useState(0);
  useEffect(() => {
    const id = setInterval(() => setTick((t) => t + 1), 1000);
    return () => clearInterval(id);
  }, []);
  const seconds = Math.max(0, Math.floor((Date.now() - at.getTime()) / 1000));
  return <span>Updated {seconds}s ago</span>;
};

const dedupeById = (items: SalesActivityItem[]): SalesActivityItem[] =>
  items.filter((item, index, self) => index === self.findIndex((t) => String(t.id) === String(item.id)));

const ALL_COLUMNS = [
  { key: 'event' as const, label: 'Event' },
  { key: 'quantity' as const, label: 'Quantity' },
  { key: 'price' as const, label: 'Amount' },
  { key: 'time' as const, label: 'Time' },
];

export const SalesActivityFeed = () => {
  const navigate = useNavigate();
  const [searchParams, setSearchParams] = useSearchParams();
  const [isExporting, setIsExporting] = useState(false);
  const [lastRefresh, setLastRefresh] = useState<Date | null>(null);
  const [liveItems, setLiveItems] = useState<SalesActivityItem[]>([]);
  const [visibleColumns, setVisibleColumns] = useState<Record<string, boolean>>({
    event: true,
    quantity: true,
    price: true,
    time: true,
  });
  const [showColumnMenu, setShowColumnMenu] = useState(false);
  const filterEventRef = useRef<HTMLSelectElement>(null);

  const initialFilters = useMemo(
    (): ActivityFilters => ({
      event: searchParams.get('event') || '',
      tier: searchParams.get('tier') || '',
      dateFrom: searchParams.get('dateFrom') || '',
      dateTo: searchParams.get('dateTo') || '',
    }),
    [searchParams],
  );

  const [filters, setFilters] = useState<ActivityFilters>(initialFilters);
  const [optimisticFilters, setOptimisticFilters] = useState<ActivityFilters>(initialFilters);

  // Optimistic UI for filters - show filtered results instantly, sync URL after
  useEffect(() => {
    setOptimisticFilters(filters);
  }, [filters]);

  // Mirror filters into the URL without disturbing unrelated params (e.g. ?eventId=).
  const searchParamsRef = useRef(searchParams);
  searchParamsRef.current = searchParams;
  useEffect(() => {
    const prev = searchParamsRef.current;
    const changed = FILTER_KEYS.some((key) => (prev.get(key) || '') !== (filters[key] || ''));
    if (!changed) return;
    setSearchParams(
      (current) => {
        const next = new URLSearchParams(current);
        for (const key of FILTER_KEYS) {
          const value = filters[key] || '';
          if (value) next.set(key, value);
          else next.delete(key);
        }
        return next;
      },
      { replace: true },
    );
  }, [filters, setSearchParams]);

  // Keyboard shortcuts: Cmd+K for create event, / for filter focus
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      // Cmd+K or Ctrl+K
      if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        navigate('/events/new');
        return;
      }
      // / for filter focus (only if not typing in input/textarea)
      if (e.key === '/' && !e.metaKey && !e.ctrlKey && !e.altKey) {
        const target = e.target as HTMLElement;
        if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable) {
          return;
        }
        e.preventDefault();
        filterEventRef.current?.focus();
      }
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [navigate]);

  const { data, isLoading, isFetching, error, refetch, dataUpdatedAt } = useSalesActivity(filters);
  const activities = useMemo(() => data ?? [], [data]);

  const handleNewActivities = useCallback((incoming: SalesActivityItem[]) => {
    setLiveItems((prev) => dedupeById([...incoming, ...prev]).slice(0, 50));
    setLastRefresh(new Date());
  }, []);

  useSalesActivitySSE(handleNewActivities);

  // Stamp refresh time when a successful fetch settles. Keyed on the query's
  // `dataUpdatedAt` (a primitive) so this never loops on array identity.
  useEffect(() => {
    if (!isFetching && !error && dataUpdatedAt) setLastRefresh(new Date(dataUpdatedAt));
  }, [isFetching, error, dataUpdatedAt]);

  const baseActivities = useMemo(() => {
    const fetchedIds = new Set(activities.map((a) => String(a.id)));
    return dedupeById([...liveItems.filter((a) => !fetchedIds.has(String(a.id))), ...activities]);
  }, [activities, liveItems]);

  const eventOptions = useMemo(() => {
    const map = new Map<string, string>();
    for (const a of baseActivities) {
      if (a.eventId && !map.has(String(a.eventId))) map.set(String(a.eventId), a.eventName || 'Untitled event');
    }
    const options = [...map].map(([value, label]) => ({ value, label }));
    if (filters.event && !map.has(filters.event)) {
      options.unshift({ value: filters.event, label: filters.event });
    }
    return [{ value: '', label: 'All Events' }, ...options];
  }, [baseActivities, filters.event]);

  const tierOptions = useMemo(() => {
    // The option value is the tier id (what the API filters on) while the
    // label stays the human-readable tier name.
    const map = new Map<string, string>();
    for (const a of baseActivities) {
      const id = String(a.tierId ?? '');
      if (id && !map.has(id)) map.set(id, a.tier || 'Unnamed tier');
    }
    const options = [...map].map(([value, label]) => ({ value, label }));
    if (filters.tier && !map.has(filters.tier)) {
      options.unshift({ value: filters.tier, label: filters.tier });
    }
    return [{ value: '', label: 'All Tiers' }, ...options];
  }, [baseActivities, filters.tier]);

  const sortedActivities = useMemo(() => {
    const activeFilters = optimisticFilters;
    const from = activeFilters.dateFrom ? new Date(activeFilters.dateFrom) : null;
    const to = activeFilters.dateTo ? new Date(activeFilters.dateTo) : null;
    if (to && !Number.isNaN(to.getTime())) to.setHours(23, 59, 59, 999);

    return baseActivities
      .filter((a) => {
        if (activeFilters.event && String(a.eventId) !== activeFilters.event) return false;
        if (activeFilters.tier && String(a.tierId ?? '') !== activeFilters.tier) return false;
        const stamp = new Date(a.timestamp).getTime();
        if (from && !Number.isNaN(from.getTime()) && stamp < from.getTime()) return false;
        if (to && !Number.isNaN(to.getTime()) && stamp > to.getTime()) return false;
        return true;
      })
      .sort((a, b) => new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime());
  }, [baseActivities, optimisticFilters]);

  const handleFilterChange = useCallback((newFilters: Partial<ActivityFilters>) => {
    setFilters((prev) => ({ ...prev, ...newFilters }));
  }, []);

  const clearFilters = useCallback(() => {
    setFilters({ event: '', tier: '', dateFrom: '', dateTo: '' });
  }, []);

  const exportToCSV = useCallback(async () => {
    if (sortedActivities.length === 0) return;
    setIsExporting(true);
    try {
      const activitiesToExport = sortedActivities.slice(0, 1000);
      const dateSuffix = filters.dateFrom && filters.dateTo
        ? `${filters.dateFrom}_to_${filters.dateTo}`
        : new Date().toISOString().slice(0, 10);
      
      // Try to export as Excel if available
      try {
        const XLSX = await import('xlsx');
        const wsData = [
          ['Timestamp', 'Event', 'Tier', 'Quantity', 'Price'],
          ...activitiesToExport.map((a) => [
            a.timestamp,
            a.eventName || '',
            a.tier || '',
            a.quantity,
            a.price,
          ]),
        ];
        const ws = XLSX.utils.aoa_to_sheet(wsData);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Activity');
        XLSX.writeFile(wb, `activity-export-${dateSuffix}.xlsx`);
        toast.success('Activity exported successfully');
        return;
      } catch {
        // Fall through to CSV
      }
      
      const headers = ['Timestamp', 'Event', 'Tier', 'Quantity', 'Price'];
      const rows = activitiesToExport.map((a) => [
        a.timestamp,
        a.eventName || '',
        a.tier || '',
        String(a.quantity),
        String(a.price),
      ]);
      const csv = [headers, ...rows]
        .map((row) => row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(','))
        .join('\r\n');

      const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `activity-export-${dateSuffix}.csv`;
      link.style.display = 'none';
      document.body.appendChild(link);
      link.click();
      window.setTimeout(() => {
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
      }, 1000);
      toast.success('Activity exported successfully');
    } catch {
      toast.error('Could not export activity');
    } finally {
      setIsExporting(false);
    }
  }, [sortedActivities, filters]);

  const hasActiveFilters = Boolean(filters.event || filters.tier || filters.dateFrom || filters.dateTo);

  const renderRows = () => {
    if (isLoading) {
      return Array.from({ length: 5 }).map((_, i) => <SkeletonRow key={`skeleton-${i}`} />);
    }
    if (error) {
      return <ErrorState onRetry={() => refetch()} />;
    }
    if (sortedActivities.length === 0) {
      return <EmptyState hasFilters={hasActiveFilters} onClearFilters={clearFilters} />;
    }

    if (sortedActivities.length > 100) {
      const RowComponent = ({ index, style }: { index: number; style: React.CSSProperties }) => {
        const activity = sortedActivities[index];
        return (
          <li style={style} className="py-2 px-0 border-b border-slate-100 last:border-0">
            <div className="grid grid-cols-[2rem_1fr] sm:grid-cols-[2rem_1fr_5.5rem_6.5rem_7rem] items-start gap-x-3 gap-y-1.5">
              <span
                className="w-8 h-8 rounded-md flex items-center justify-center bg-indigo-100 text-indigo-700 text-xs font-bold flex-shrink-0"
                aria-hidden="true"
              >
                {activity.eventInitials || 'EV'}
              </span>
              <div className="min-w-0">
                <p className="text-sm font-medium text-slate-800 truncate" title={activity.eventName}>
                  {activity.eventName || 'Unknown Event'}
                </p>
                {visibleColumns.event && <p className="text-xs text-slate-500 truncate">{activity.tier || 'All Tiers'}</p>}
              </div>
              {visibleColumns.quantity && (
                <p className="hidden sm:block text-sm text-slate-700 text-right whitespace-nowrap">
                  {activity.quantity > 0 ? `${activity.quantity} ×` : '—'}
                </p>
              )}
              {visibleColumns.price && (
                <p className="hidden sm:block text-sm font-semibold text-slate-800 text-right whitespace-nowrap">
                  ₦{Number(activity.price || 0).toLocaleString()}
                </p>
              )}
              {visibleColumns.time && (
                <p className="hidden sm:block text-xs text-slate-400 text-right whitespace-nowrap">
                  <TimeAgo value={activity.timestamp} />
                </p>
              )}
              <p className="col-span-2 sm:hidden text-xs text-slate-500 flex flex-wrap gap-x-3">
                {visibleColumns.price && <span className="font-semibold text-slate-700">₦{Number(activity.price || 0).toLocaleString()}</span>}
                {visibleColumns.quantity && <span>{activity.quantity > 0 ? `${activity.quantity} tickets` : 'No tickets'}</span>}
                {visibleColumns.time && (
                  <span className="text-slate-400">
                    <TimeAgo value={activity.timestamp} />
                  </span>
                )}
              </p>
            </div>
          </li>
        );
      };
      return (
        <List
          rowComponent={({ index, style }: any) => <RowComponent index={index} style={style} />}
          rowCount={sortedActivities.length}
          rowHeight={60}
          overscanCount={5}
          style={{ height: 384, width: '100%' }}
          rowProps={{}}
        />
      );
    }

    return sortedActivities.map((activity) => (
      <li
        key={activity.id}
        className="py-3 border-b border-slate-100 last:border-0 last:pb-0 first:pt-0"
      >
        <div className="grid grid-cols-[2rem_1fr] sm:grid-cols-[2rem_1fr_5.5rem_6.5rem_7rem] items-start gap-x-3 gap-y-1.5">
          <span
            className="w-8 h-8 rounded-md flex items-center justify-center bg-indigo-100 text-indigo-700 text-xs font-bold flex-shrink-0"
            aria-hidden="true"
          >
            {activity.eventInitials || 'EV'}
          </span>
          <div className="min-w-0">
            <p className="text-sm font-medium text-slate-800 truncate" title={activity.eventName}>
              {activity.eventName || 'Unknown Event'}
            </p>
            {visibleColumns.event && <p className="text-xs text-slate-500 truncate">{activity.tier || 'All Tiers'}</p>}
          </div>
          {visibleColumns.quantity && (
            <p className="hidden sm:block text-sm text-slate-700 text-right whitespace-nowrap">
              {activity.quantity > 0 ? `${activity.quantity} ×` : '—'}
            </p>
          )}
          {visibleColumns.price && (
            <p className="hidden sm:block text-sm font-semibold text-slate-800 text-right whitespace-nowrap">
              ₦{Number(activity.price || 0).toLocaleString()}
            </p>
          )}
          {visibleColumns.time && (
            <p className="hidden sm:block text-xs text-slate-400 text-right whitespace-nowrap">
              <TimeAgo value={activity.timestamp} />
            </p>
          )}
          <p className="col-span-2 sm:hidden text-xs text-slate-500 flex flex-wrap gap-x-3">
            {visibleColumns.price && <span className="font-semibold text-slate-700">₦{Number(activity.price || 0).toLocaleString()}</span>}
            {visibleColumns.quantity && <span>{activity.quantity > 0 ? `${activity.quantity} tickets` : 'No tickets'}</span>}
            {visibleColumns.time && (
              <span className="text-slate-400">
                <TimeAgo value={activity.timestamp} />
              </span>
            )}
          </p>
        </div>
      </li>
    ));
  };

  return (
    <ErrorBoundary
      fallbackRender={(error, reset) => (
        <div className="bg-white rounded-xl border border-rose-200 p-6" role="alert">
          <div className="flex items-start gap-3">
            <svg className="w-5 h-5 text-rose-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
              <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
            </svg>
            <div>
              <h3 className="text-lg font-semibold text-rose-800">Activity Feed Unavailable</h3>
              <p className="text-sm text-rose-600 mt-1">{error.message}</p>
              <button
                onClick={reset}
                className="mt-3 px-3 py-1.5 text-sm font-medium text-rose-700 bg-rose-100 border border-rose-200 rounded-lg hover:bg-rose-200"
              >
                Retry
              </button>
            </div>
          </div>
        </div>
      )}
    >
      <section className="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden" aria-labelledby="activity-feed-title">
        <div className="px-4 py-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
          <div className="min-w-0">
            <h2 className="text-sm font-semibold text-slate-800" id="activity-feed-title">
              Sales Activity Feed
            </h2>
            {lastRefresh && (
              <p className="text-xs text-slate-400 mt-0.5 flex items-center gap-1">
                <RefreshedAgo at={lastRefresh} />
                {isFetching && <span className="text-indigo-500" aria-hidden="true">⟳</span>}
              </p>
            )}
          </div>
          <div className="flex items-center gap-2">
            <div className="relative">
              <button
                type="button"
                onClick={() => setShowColumnMenu(!showColumnMenu)}
                className="px-3 py-1.5 rounded-md text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 border border-slate-200"
              >
                Columns
              </button>
              {showColumnMenu && (
                <div className="absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-lg border border-slate-200 z-10">
                  {ALL_COLUMNS.map((col) => (
                    <label key={col.key} className="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 cursor-pointer">
                      <input
                        type="checkbox"
                        checked={visibleColumns[col.key]}
                        onChange={(e) => setVisibleColumns((prev) => ({ ...prev, [col.key]: e.target.checked }))}
                        className="rounded"
                      />
                      <span className="text-xs text-slate-700">{col.label}</span>
                    </label>
                  ))}
                </div>
              )}
            </div>
            <button
              type="button"
              onClick={exportToCSV}
              disabled={isExporting || sortedActivities.length === 0}
              className={`px-3 py-1.5 rounded-md text-xs font-semibold transition-colors w-full sm:w-auto ${
                isExporting || sortedActivities.length === 0
                  ? 'opacity-50 cursor-not-allowed bg-slate-100 text-slate-400'
                  : 'text-indigo-600 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200'
              }`}
              aria-label={isExporting ? 'Exporting activity…' : 'Export Activity to CSV'}
            >
              {isExporting ? 'Exporting…' : 'Export Activity'}
            </button>
          </div>
        </div>

        <div className="px-4 py-3 border-b border-slate-100 bg-slate-50/60">
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
              <label htmlFor="filter-event" className="block text-xs font-medium text-slate-600 mb-1">Event</label>
              <select
                ref={filterEventRef}
                id="filter-event"
                className="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                value={filters.event}
                onChange={(e) => handleFilterChange({ event: e.target.value })}
              >
                {eventOptions.map((opt) => (
                  <option key={opt.value} value={opt.value}>{opt.label}</option>
                ))}
              </select>
            </div>
            <div>
              <label htmlFor="filter-tier" className="block text-xs font-medium text-slate-600 mb-1">Tier</label>
              <select
                id="filter-tier"
                className="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                value={filters.tier}
                onChange={(e) => handleFilterChange({ tier: e.target.value })}
              >
                {tierOptions.map((opt) => (
                  <option key={opt.value} value={opt.value}>{opt.label}</option>
                ))}
              </select>
            </div>
            <div>
              <label htmlFor="filter-date-from" className="block text-xs font-medium text-slate-600 mb-1">Date From</label>
              <input
                id="filter-date-from"
                type="date"
                className="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                value={filters.dateFrom}
                onChange={(e) => handleFilterChange({ dateFrom: e.target.value })}
              />
            </div>
            <div>
              <label htmlFor="filter-date-to" className="block text-xs font-medium text-slate-600 mb-1">Date To</label>
              <input
                id="filter-date-to"
                type="date"
                className="w-full px-2.5 py-1.5 border border-slate-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                value={filters.dateTo}
                onChange={(e) => handleFilterChange({ dateTo: e.target.value })}
              />
            </div>
          </div>
          {hasActiveFilters && (
            <button
              type="button"
              onClick={clearFilters}
              className="mt-2 text-xs text-indigo-600 hover:text-indigo-800 font-semibold"
            >
              Clear all filters
            </button>
          )}
        </div>

        <div className="px-4 py-2 max-h-96 overflow-y-auto" aria-busy={isLoading || isFetching}>
          <ul className="text-sm">{renderRows()}</ul>
        </div>
      </section>
    </ErrorBoundary>
  );
};

export default SalesActivityFeed;
