import React, { useState, useEffect, useCallback, useRef } from 'react';
import PropTypes from 'prop-types';

const AUTO_UPDATE_INTERVAL = 30 * 1000;

const formatCurrency = (amount) => {
  if (amount === null || amount === undefined) return '—';
  try {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount);
  } catch {
    return String(amount);
  }
};

const formatTimestamp = (ts) => {
  if (!ts) return '—';
  const d = new Date(ts);
  if (isNaN(d.getTime())) return '—';
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

const formatDate = (ts) => {
  if (!ts) return '—';
  const d = new Date(ts);
  if (isNaN(d.getTime())) return '—';
  return d.toLocaleDateString();
};

const generateId = () => Math.random().toString(36).substring(2, 9);

export const SalesActivityFeed = ({
  activities: initialActivities = [],
  onFilterChange,
  onExport,
  autoUpdate = true,
}) => {
  const [activities, setActivities] = useState([...initialActivities]);
  const [eventFilter, setEventFilter] = useState('all');
  const [tierFilter, setTierFilter] = useState('all');
  const [dateRange, setDateRange] = useState('all');
  const [isExporting, setIsExporting] = useState(false);
  const intervalRef = useRef(null);

  const uniqueEvents = useCallback(() => {
    const events = new Set(activities.map((a) => a.event));
    return Array.from(events);
  }, [activities]);

  const uniqueTiers = useCallback(() => {
    const tiers = new Set(activities.map((a) => a.tier));
    return Array.from(tiers);
  }, [activities]);

  useEffect(() => {
    if (!autoUpdate) return;

    const startPolling = () => {
      intervalRef.current = setInterval(() => {
        setActivities((prev) => {
          const events = ['Summer Festival 2026', 'Winter Gala Dinner', 'Spring Concert Series', 'Tech Summit 2026'];
          const tiers = ['Early Bird', 'Standard', 'VIP', 'Premium'];
          const randomEvent = events[Math.floor(Math.random() * events.length)];
          const randomTier = tiers[Math.floor(Math.random() * tiers.length)];
          const newItem = {
            id: generateId(),
            event: randomEvent,
            tier: randomTier,
            quantity: Math.floor(Math.random() * 5) + 1,
            price: Math.round((Math.random() * 200 + 10) * 100) / 100,
            timestamp: new Date().toISOString(),
          };
          return [newItem, ...prev].slice(0, 50);
        });
      }, AUTO_UPDATE_INTERVAL);
    };

    const stopPolling = () => {
      if (intervalRef.current) {
        clearInterval(intervalRef.current);
        intervalRef.current = null;
      }
    };

    // Start polling when visible
    if (document.visibilityState === 'visible') {
      startPolling();
    }

    // Pause when tab is hidden, resume when visible
    const handleVisibilityChange = () => {
      if (document.visibilityState === 'visible') {
        startPolling();
      } else {
        stopPolling();
      }
    };

    document.addEventListener('visibilitychange', handleVisibilityChange);

    return () => {
      stopPolling();
      document.removeEventListener('visibilitychange', handleVisibilityChange);
    };
  }, [autoUpdate]);

  const filteredActivities = activities.filter((a) => {
    if (eventFilter !== 'all' && a.event !== eventFilter) return false;
    if (tierFilter !== 'all' && a.tier !== tierFilter) return false;
    if (dateRange !== 'all') {
      const itemDate = new Date(a.timestamp).toDateString();
      const today = new Date().toDateString();
      const weekAgo = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toDateString();
      const monthAgo = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toDateString();
      if (dateRange === 'today' && itemDate !== today) return false;
      if (dateRange === 'week' && (itemDate < weekAgo || itemDate > today)) return false;
      if (dateRange === 'month' && (itemDate < monthAgo || itemDate > today)) return false;
    }
    return true;
  });

  const handleExport = useCallback(() => {
    setIsExporting(true);
    const headers = ['Event', 'Tier', 'Quantity', 'Price', 'Timestamp'];
    const rows = filteredActivities.map((a) => [
      a.event,
      a.tier,
      a.quantity,
      formatCurrency(a.price),
      formatTimestamp(a.timestamp),
    ]);
    const csvContent = [headers.join(','), ...rows.map((r) => r.join(','))].join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', `sales-activity-${formatDate(new Date())}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    setIsExporting(false);
    onExport?.(filteredActivities.length);
  }, [filteredActivities, onExport, formatCurrency, formatTimestamp, formatDate]);

  return (
    <div className="border border-slate-100 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-900 shadow-sm flex flex-col h-full">
      <div className="p-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
        <h2 className="text-lg font-bold text-slate-900 dark:text-slate-100">Sales Activity</h2>
        <div className="flex items-center gap-3">
          <span className="relative flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-medium bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border border-green-100 dark:border-green-900/30">
            <span className="h-2 w-2 rounded-full bg-green-500 animate-pulse" />
            Live
          </span>
          <button
            onClick={handleExport}
            disabled={isExporting || filteredActivities.length === 0}
            className={`px-3 py-1.5 text-xs font-semibold rounded-lg border transition-colors disabled:opacity-50 disabled:cursor-not-allowed ${
              isExporting || filteredActivities.length === 0
                ? 'bg-slate-100 dark:bg-slate-800 text-slate-400 border-slate-200 dark:border-slate-600'
                : 'bg-indigo-600 dark:bg-indigo-500 text-white border-indigo-600 dark:border-indigo-500 hover:bg-indigo-700 dark:hover:bg-indigo-600'
            }`}
          >
            {isExporting ? 'Exporting…' : 'Export Activity'}
          </button>
        </div>
      </div>

      <div className="p-4">
        <div className="flex flex-wrap gap-3 mb-4">
          <div className="relative">
            <select
              value={eventFilter}
              onChange={(e) => {
                setEventFilter(e.target.value);
                onFilterChange?.({ event: e.target.value });
              }}
              className="w-40 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-200 text-xs font-medium p-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
              <option value="all">All Events</option>
              {uniqueEvents().map((e) => (
                <option key={e} value={e}>{e}</option>
              ))}
            </select>
          </div>
          <div className="relative">
            <select
              value={tierFilter}
              onChange={(e) => {
                setTierFilter(e.target.value);
                onFilterChange?.({ tier: e.target.value });
              }}
              className="w-32 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-200 text-xs font-medium p-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
              <option value="all">All Tiers</option>
              {uniqueTiers().map((t) => (
                <option key={t} value={t}>{t}</option>
              ))}
            </select>
          </div>
          <div className="relative">
            <select
              value={dateRange}
              onChange={(e) => {
                setDateRange(e.target.value);
                onFilterChange?.({ dateRange: e.target.value });
              }}
              className="w-32 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-200 text-xs font-medium p-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
              <option value="all">All Dates</option>
              <option value="today">Today</option>
              <option value="week">This Week</option>
              <option value="month">This Month</option>
            </select>
          </div>
        </div>

        <div className="overflow-y-auto">
          {filteredActivities.length === 0 ? (
            <div className="p-8 text-center">
              <p className="text-sm text-slate-500">No activity found.</p>
            </div>
          ) : (
            <div className="divide-y divide-slate-50">
              {filteredActivities.map((activity) => (
                <div key={activity.id} className="p-3 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors flex items-center justify-between group">
                  <div className="flex items-center gap-3">
                    <span className={`px-2 py-0.5 rounded text-xs font-semibold border ${activity.tier === 'VIP' ? 'bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-400 border-purple-100 dark:border-purple-900/30' : activity.tier === 'Early Bird' ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 border-indigo-100 dark:border-indigo-900/30' : activity.tier === 'Standard' ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border-blue-100 dark:border-blue-900/30' : 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border-amber-100 dark:border-amber-900/30'}`}>
                      {activity.tier}
                    </span>
                    <div>
                      <div className="text-sm font-semibold text-slate-900 dark:text-slate-100">{activity.event}</div>
                      <div className="text-xs text-slate-500 dark:text-slate-400">
                        {activity.quantity} ticket{activity.quantity !== 1 ? 's' : ''} • {formatTimestamp(activity.timestamp)}
                      </div>
                    </div>
                  </div>
                  <div className="text-right">
                    <div className="text-sm font-bold text-slate-900 dark:text-slate-100">{formatCurrency(activity.price * activity.quantity)}</div>
                    <div className="text-xs text-slate-400 dark:text-slate-500">{formatCurrency(activity.price)} each</div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        <div className="border-t border-slate-100 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 p-3 flex justify-between items-center text-xs">
          <span className="text-slate-500 dark:text-slate-400">
            {filteredActivities.length} of {activities.length} activity items
          </span>
          <span className="text-slate-500 dark:text-slate-400">Auto-updates every 30s</span>
        </div>
      </div>
    </div>
  );
};

SalesActivityFeed.displayName = 'SalesActivityFeed';

SalesActivityFeed.propTypes = {
  activities: PropTypes.arrayOf(PropTypes.shape({
    id: PropTypes.string.isRequired,
    event: PropTypes.string.isRequired,
    tier: PropTypes.string.isRequired,
    quantity: PropTypes.number.isRequired,
    price: PropTypes.number.isRequired,
    timestamp: PropTypes.string.isRequired,
  })),
  onFilterChange: PropTypes.func,
  onExport: PropTypes.func,
  autoUpdate: PropTypes.bool,
};

SalesActivityFeed.defaultProps = {
  activities: [],
  onFilterChange: undefined,
  onExport: undefined,
  autoUpdate: true,
};

export default SalesActivityFeed;
