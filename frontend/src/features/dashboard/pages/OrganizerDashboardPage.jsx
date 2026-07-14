import React from 'react';
import { useDashboardPreferences } from '../hooks/useDashboardPreferences';

const OrganizerDashboardPage = () => {
  const {
    expandedEventId,
    filters,
    isActivityFeedVisible,
    setExpandedEventId,
    setFilters,
    toggleActivityFeed,
    setActivityFeedVisible,
  } = useDashboardPreferences();

  // Simple event options to expand
  const eventsList = [
    { id: 1, title: 'Summer Festival 2026', desc: 'Outdoor arts and music festival with 5 stages.' },
    { id: 2, title: 'Winter Gala Dinner', desc: 'Premium black-tie charity dinner and live auction.' },
    { id: 3, title: 'Spring Concert Series', desc: 'Intimate classical orchestral performances.' },
  ];

  return (
    <div className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="mx-auto max-w-7xl">
        {/* Header */}
        <div className="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">Organizer Dashboard</h1>
            <p className="mt-2 text-sm text-slate-500">
              Manage event allocations, operational layouts, and lightweight preference states.
            </p>
          </div>
          <div className="flex items-center gap-2">
            <span className="text-xs font-semibold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-full border border-indigo-100 flex items-center gap-1.5">
              <span className="h-2 w-2 bg-indigo-500 rounded-full" />
              Zustand LocalStorage Persisted
            </span>
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Main Panel: Events List with Expanded Accordions */}
          <div className="lg:col-span-2 space-y-6">
            <div className="bg-white p-6 rounded-xl border border-slate-100 shadow-sm">
              <div className="flex items-center justify-between mb-4">
                <h2 className="text-lg font-bold text-slate-800">Your Active Events</h2>
                <span className="text-xs text-slate-400">Click an event to expand details</span>
              </div>

              <div className="space-y-3">
                {eventsList.map((event) => {
                  const isExpanded = expandedEventId === event.id;
                  return (
                    <div 
                      key={event.id}
                      className={`border rounded-lg transition-all overflow-hidden ${
                        isExpanded ? 'border-indigo-200 bg-indigo-50/10' : 'border-slate-100 bg-white hover:border-slate-200'
                      }`}
                    >
                      <button
                        onClick={() => setExpandedEventId(isExpanded ? null : event.id)}
                        className="w-full text-left p-4 flex items-center justify-between"
                      >
                        <div>
                          <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Event ID: {event.id}</span>
                          <span className="font-bold text-slate-800 text-sm">{event.title}</span>
                        </div>
                        <span className={`text-xs px-2.5 py-1 rounded-md font-medium transition-all ${
                          isExpanded ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600'
                        }`}>
                          {isExpanded ? 'Expanded' : 'Expand'}
                        </span>
                      </button>

                      {isExpanded && (
                        <div className="p-4 border-t border-slate-100 bg-slate-50/50 text-slate-600 text-xs animate-fadeIn">
                          <p className="mb-2 leading-relaxed">{event.desc}</p>
                          <div className="flex gap-2 mt-3">
                            <span className="px-2 py-0.5 bg-indigo-50 border border-indigo-100 text-indigo-600 font-semibold rounded text-[10px]">
                              Operational status: Active
                            </span>
                            <span className="px-2 py-0.5 bg-slate-100 text-slate-500 rounded text-[10px]">
                              Persisted index: {event.id}
                            </span>
                          </div>
                        </div>
                      )}
                    </div>
                  );
                })}
              </div>
            </div>

            {/* Filter controls demonstration */}
            <div className="bg-white p-6 rounded-xl border border-slate-100 shadow-sm">
              <h2 className="text-lg font-bold text-slate-800 mb-4">Interactive Filter Store</h2>
              <p className="text-xs text-slate-500 mb-4">
                Modify these filter preferences. Because they use Zustand's <code>persist</code> middleware, they will survive page refreshes.
              </p>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Date Range</label>
                  <select
                    value={filters.dateRange}
                    onChange={(e) => setFilters({ dateRange: e.target.value })}
                    className="w-full bg-slate-50 border border-slate-200 text-slate-700 text-xs font-semibold p-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                  >
                    <option value="all">All Dates</option>
                    <option value="today">Today</option>
                    <option value="this-week">This Week</option>
                    <option value="this-month">This Month</option>
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Event Status</label>
                  <select
                    value={filters.status}
                    onChange={(e) => setFilters({ status: e.target.value })}
                    className="w-full bg-slate-50 border border-slate-200 text-slate-700 text-xs font-semibold p-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                  >
                    <option value="all">All Statuses</option>
                    <option value="active">Active Only</option>
                    <option value="completed">Completed Only</option>
                    <option value="cancelled">Cancelled Only</option>
                  </select>
                </div>
              </div>

              <div className="mt-4">
                <label className="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Global Search Query</label>
                <input
                  type="text"
                  placeholder="Type to search events..."
                  value={filters.search}
                  onChange={(e) => setFilters({ search: e.target.value })}
                  className="w-full bg-slate-50 border border-slate-200 text-slate-700 text-xs font-medium p-2.5 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
              </div>
            </div>
          </div>

          {/* Right Sidebar: Preferences inspection */}
          <div className="space-y-6">
            {/* Activity Feed Toggle Card */}
            <div className="bg-white p-6 rounded-xl border border-slate-100 shadow-sm">
              <h2 className="text-lg font-bold text-slate-800 mb-2">Feed Controller</h2>
              <p className="text-xs text-slate-500 mb-4">Toggle visibility of the real-time activity feed.</p>

              <button
                onClick={toggleActivityFeed}
                className={`w-full py-2.5 px-4 rounded-lg font-bold text-xs transition-all ${
                  isActivityFeedVisible
                    ? 'bg-rose-50 text-rose-600 border border-rose-100'
                    : 'bg-indigo-600 text-white shadow-sm'
                }`}
              >
                {isActivityFeedVisible ? 'Hide Activity Feed' : 'Show Activity Feed'}
              </button>
            </div>

            {/* Live Store Inspector */}
            <div className="bg-slate-900 text-slate-200 p-6 rounded-xl shadow-xl font-mono text-xs">
              <div className="flex items-center justify-between mb-4 border-b border-slate-800 pb-3">
                <span className="font-bold text-indigo-400">Live Zustand Inspect</span>
                <span className="text-[10px] bg-slate-800 px-2 py-0.5 rounded text-slate-400 font-bold">STATE</span>
              </div>
              <pre className="overflow-x-auto whitespace-pre-wrap leading-relaxed">
{JSON.stringify({
  expandedEventId,
  filters,
  isActivityFeedVisible
}, null, 2)}
              </pre>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default OrganizerDashboardPage;

