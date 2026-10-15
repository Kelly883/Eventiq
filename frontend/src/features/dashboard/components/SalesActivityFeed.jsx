import React, { useState, useEffect, useRef, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { api } from '../../../lib/api';
import { toast } from 'react-hot-toast';

const ACTIVITY_REFRESH_INTERVAL = 30000;

const SalesActivityFeed = () => {
  const [activities, setActivities] = useState([]);
  const [filters, setFilters] = useState({ event: '', tier: '', dateFrom: '', dateTo: '' });
  const [isExporting, setIsExporting] = useState(false);
  const [lastRefresh, setLastRefresh] = useState<Date | null>(null);
  const timeoutRef = useRef(null);

  const fetchActivities = useCallback(async () => {
    try {
      const response = await api.get('/activity/feed', { params: filters });
      const newActivities = response.data?.activities || response.data || [];
      setActivities(prev => {
        const merged = [...newActivities, ...prev].filter(
          (item, index) => index < prev.length ? item.timestamp > prev[index].timestamp : true
        );
        return merged.slice(0, 50);
      });
      setLastRefresh(new Date());
    } catch (err) {
      toast.error('Failed to fetch activity feed');
      console.error('Failed to fetch activity feed:', err);
    }
  }, [filters]);

  useEffect(() => {
    fetchActivities();
    timeoutRef.current = setInterval(() => fetchActivities(), ACTIVITY_REFRESH_INTERVAL);
    return () => clearInterval(timeoutRef.current);
  }, [fetchActivities]);

  const handleFilterChange = useCallback((newFilters) => {
    setFilters(newFilters);
    fetchActivities();
  }, [fetchActivities]);

  const exportToCSV = () => {
    setIsExporting(true);
    const activitiesToExport = activities.slice(0, 1000);
    const headers = ['Timestamp', 'Event', 'Tier', 'Quantity', 'Price'];
    const rows = activitiesToExport.map((a) => [
      a.timestamp,
      a.eventName || a.event || '',
      a.tier || '',
      a.quantity || 0,
      `${a.price?.toLocaleString() || 0}`,
    ]);
    const csv = [headers, ...rows].map(row =>
      row.map(cell => `"${String(cell).replace(/"/g, '""')}"`.replace(/,/g, '').replace(/\\n/g, ' '))
    ).join(',\n');

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', `activity-export-${new Date().toISOString().slice(0, 10)}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    setIsExporting(false);
    toast.success('Activity exported successfully');
  };

  const filteredActivities = activities.filter((a) => {
    if (filters.event && (a.eventName || a.event || '').indexOf(filters.event) === -1) return false;
    if (filters.tier && (a.tier || '').indexOf(filters.tier) === -1) return false;
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

  const sortedActivities = filteredActivities.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));

  return (
    <div className="bg-white rounded-xl border border-slate-200 shadow-sm mb-6">
      <div className="px-4 py-3 border-b border-slate-100">
        <h2 className="text-sm font-semibold text-slate-800" id="activity-feed-title">Sales Activity Feed</h2>
        <div className="flex items-center gap-2 ml-4">
          <button
            type="button"
            onClick={exportToCSV}
            disabled={isExporting}
            className={`px-3 py-1.5 rounded-md text-xs font-medium transition-colors ${
              isExporting ? 'opacity-50 cursor-not-allowed' : 'text-primary hover:bg-primary-50'
            }`}
            aria-label={isExporting ? 'Exporting activity...' : 'Export Activity to CSV'}
          >
            {isExporting ? 'Exporting…' : 'Export Activity'}
          </button>
          {lastRefresh && (
            <span className="text-xs text-slate-400 ml-4">
              Refreshed {Math.floor(
                (new Date().getTime() - lastRefresh.getTime()) / 1000
              )} seconds ago
            </span>
          )}
        </div>
      </div>

      <div className="px-4 py-2 border-b border-slate-100">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
          <div>
            <label className="block text-xs font-medium text-slate-600 mb-1">Event</label>
            <select
              className="w-full px-2 py-1 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              onChange={(e) => handleFilterChange({ ...filters, event: e.target.value })}
            >
              <option value="">All Events</option>
              <option value="concert-2024">Concert Q4 2024</option>
              <option value="summit-2025">Summit 2025</option>
              <option value="workshop-series">Workshop Series</option>
            </select>
          </div>
          <div>
            <label className="block text-xs font-medium text-slate-600 mb-1">Tier</label>
            <select
              className="w-full px-2 py-1 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              onChange={(e) => handleFilterChange({ ...filters, tier: e.target.value })}
            >
              <option value="">All Tiers</option>
              <option value="general">General Admission</option>
              <option value="vip">VIP</option>
              <option value="premium">Premium</option>
              <option value="platinum">Platinum</option>
            </select>
          </div>
          <div>
            <label className="block text-xs font-medium text-slate-600 mb-1">Date From</label>
            <input
              type="date"
              className="w-full px-2 py-1 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              value={filters.dateFrom}
              onChange={(e) => handleFilterChange({ ...filters, dateFrom: e.target.value })}
            />
          </div>
          <div>
            <label className="block text-xs font-medium text-slate-600 mb-1">Date To</label>
            <input
              type="date"
              className="w-full px-2 py-1 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
              value={filters.dateTo}
              onChange={(e) => handleFilterChange({ ...filters, dateTo: e.target.value })}
            />
          </div>
        </div>
      </div>

      <div className="px-4 py-4 max-h-96 overflow-y-auto">
        {sortedActivities.length === 0 && (
          <div className="text-center py-8 text-slate-500">
            <span className="block text-lg mb-2">No activity</span>
            <p>No sales activity matches the selected filters.</p>
          </div>
        )}
        {sortedActivities.map((activity, index) => (
          <div
            key={activity.id}
            className="event-card mb-3 pb-3 border-b border-slate-100 last:border-0"
            style={{ animationDelay: `${index * 0.05}s` }}
          >
            <div className="flex items-start gap-3">
              <div className="w-8 h-8 rounded-md flex items-center justify-center flex-shrink-0 bg-primary-100 text-primary mr-3">
                <span className="text-sm font-medium">{activity.eventInitials || 'E'}</span>
              </div>
              <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-slate-800">{activity.eventName || activity.event || 'Unknown Event'}</p>
                <p className="text-xs text-slate-500">{activity.tier || 'All Tiers'}</p>
              </div>
              <div className="w-20">
                <p className="text-sm font-medium text-slate-700">{activity.quantity > 0 ? `${activity.quantity} tickets` : '0 tickets'}</p>
              </div>
              <div className="w-24">
                <p className="text-sm font-medium text-slate-700">₦{activity.price?.toLocaleString() || '0'}</p>
              </div>
              <p className="text-xs text-slate-400 ml-auto">{activity.timestamp || ''}</p>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default SalesActivityFeed;