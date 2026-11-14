import React from 'react';
import { useMemo } from 'react';

export const ActivityFeed = ({ loading, data, filter = '' }) => {
  if (loading) return <div className="animate-pulse h-32 bg-gray-100 rounded" aria-live="polite" aria-busy="true" />;

  const filteredActivity = useMemo(() => {
    if (!filter) return data || [];
    const lowercaseFilter = filter.toLowerCase();
    return (data || []).filter(
      (item) =>
        (item.action && item.action.toLowerCase().includes(lowercaseFilter)) ||
        (item.description && item.description.toLowerCase().includes(lowercaseFilter)) ||
        (item.targetType && item.targetType.toLowerCase().includes(lowercaseFilter)) ||
        (item.user && `${item.user.name} ${item.user.id}`.toLowerCase().includes(lowercaseFilter))
    );
  }, [data, filter]);

  const hasOriginalData = data && data.length > 0;
  const hasMatchingData = filteredActivity && filteredActivity.length > 0;

  if (!hasMatchingData) {
    if (!hasOriginalData) {
      return (
        <div className="p-4 text-center">
          <div className="h-48 flex items-center justify-center border-2 border-dashed border-gray-300 rounded mb-3">
            <svg className="w-12 h-12 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5"><path d="M21 15l-3-3l-3 3l-3-3l3-3l3 3l3-3l-3-3l-3 3z"/><circle cx="12" cy="12" r="10"/></svg>
          </div>
          <p className="text-sm text-gray-500">No activity</p>
          {filter && <p className="text-xs text-gray-400">Try adjusting the filter or <span className="underline cursor-pointer">clear search</span></p>}
        </div>
      );
    } else {
      return (
        <div className="p-4 text-center">
          <div className="h-48 flex items-center justify-center border-2 border-dashed border-gray-300 rounded mb-3">
            <svg className="w-12 h-12 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5"><path d="M21 15l-3-3l-3 3l-3-3l3-3l3 3l3-3l-3-3l-3 3z"/><circle cx="12" cy="12" r="10"/></svg>
          </div>
          <p className="text-sm text-gray-500">No activity matching filters</p>
          <p className="text-xs text-gray-400">Try adjusting the filter or <span className="underline cursor-pointer">clear search</span></p>
        </div>
      );
    }
  }

  const truncateName = (name, maxLength = 30) => {
    if (!name) return '';
    if (name.length <= maxLength) return name;
    return name.substring(0, maxLength - 1) + '…';
  };

  return (
    <div className="p-4" aria-label="Activity feed">
      <h2 className="font-semibold text-gray-900" aria-label="Activity feed title">Activity</h2>
      <div className="mt-2 flex items-center gap-2">
        <input
          type="text"
          value={filter}
          placeholder="Filter activities..."
          className="flex-1 rounded border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
          aria-label="Filter activities"
        />
        <button className="px-3 py-2 rounded border border-gray-300 text-sm hover:bg-gray-100 transition-colors" aria-label="Clear filter">Clear</button>
      </div>
      <div className="mt-4 overflow-x-auto">
        <table className="w-full min-w-table rounded border border-gray-200">
          <thead>
            <tr className="bg-gray-50">
              <th className="p-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" scope="col">Action</th>
              <th className="p-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" scope="col">Target</th>
              <th className="p-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" scope="col">User</th>
              <th className="p-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" scope="col">Date</th>
            </tr>
          </thead>
          <tbody>
            {filteredActivity.map((item) => {
              const actionLabel = item.action?.replace(/_/g, ' ') || '—';
              const targetType = item.targetType || '—';
              const userName = item.user?.name ? truncateName(item.user.name) : '—';
              const createdAt = item.createdAt ? new Date(item.createdAt).toLocaleString() : '—';
              return (
                <tr key={item.id} className="hover:bg-gray-50 transition-colors">
                  <td className="p-3 text-sm font-medium text-gray-900">{actionLabel}</td>
                  <td className="p-3 text-sm text-gray-500">{targetType}</td>
                  <td className="p-3 text-sm text-gray-500">{userName}</td>
                  <td className="p-3 text-sm text-gray-400">{createdAt}</td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
      {filteredActivity.length > 0 && (
        <p className="mt-2 text-xs text-gray-500" aria-describedby="trends-description">
          Showing {filteredActivity.length} activity item{s} filtered from recent admin actions
          <span id="trends-description" className="sr-only">(last 30s polling interval, sortable columns available)</span>
        </p>
      )}
    </div>
  );
};