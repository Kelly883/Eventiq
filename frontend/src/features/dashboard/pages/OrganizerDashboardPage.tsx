import React, { useEffect } from 'react';
import { Link, useLocation, useNavigate } from 'react-router-dom';
import { useDashboardPreferences } from '../hooks/useDashboardPreferences';
import { useEvents, useDashboardMetrics, EventData } from '../hooks/useEvents';
import EventDetailWidget from '../components/EventDetailWidget';
import { DashboardEventCard } from '../components/DashboardEventCard';
import { MetricCardRow } from '../components/MetricCard';
import { DashboardHeader } from '../components/dashboard/DashboardHeader';
import { SalesActivityFeed } from '../components/SalesActivityFeed';
import { useAuthContext } from '../../auth/context/AuthContext';
import { getUserRoles } from '../../../lib/authRoles';

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

  const location = useLocation();
  const navigate = useNavigate();
  const { user } = useAuthContext();
  const roles = getUserRoles(user);

  // Deep linking: expand event from query param ?eventId=xyz
  useEffect(() => {
    const urlParams = new URLSearchParams(location.search);
    const deepEventId = urlParams.get('eventId');
    if (deepEventId && expandedEventId !== parseInt(deepEventId, 10)) {
      setExpandedEventId(parseInt(deepEventId, 10));
    }
  }, [location.search, expandedEventId, setExpandedEventId]);

  const { data: events = [], isLoading: eventsLoading, error: eventsError, refetch: refetchEvents } = useEvents();

  const handleFilterChange = (filter: string) => {
    setFilters({ ...filters, status: filter });
  };

  const handleDateRangeChange = (range: { from: Date; to: Date }) => {
    const days = Math.ceil((range.to.getTime() - range.from.getTime()) / (1000 * 60 * 60 * 24));
    let dateRange = 'all';
    if (days <= 7) dateRange = 'today';
    else if (days <= 30) dateRange = 'this-week';
    else if (days <= 90) dateRange = 'this-month';
    setFilters({ ...filters, dateRange });
  };

  const handleCreateEvent = () => {
    navigate('/organizer/events/new');
  };

  const eventMap = events.length > 0
    ? events.reduce((map, ev) => ({ ...map, [ev.id]: ev }), {})
    : {};

  return (
    <div className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="mx-auto max-w-7xl">
        {/* Header */}
        <DashboardHeader
          user={user}
          onFilterChange={handleFilterChange}
          onDateRangeChange={handleDateRangeChange}
          onCreateEvent={handleCreateEvent}
        />

        {/* Metrics Row */}
        <MetricCardRow />

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-8">
          {/* Main Panel: Events List with Expanded Accordions */}
          <div className="lg:col-span-2 space-y-6">
            <div className="bg-white p-6 rounded-xl border border-slate-100 shadow-sm">
              <div className="flex items-center justify-between mb-4">
                <h2 className="text-lg font-bold text-slate-800">Your Active Events</h2>
                <span className="text-xs text-slate-400">Click an event to expand details</span>
              </div>

              <div className="space-y-3">
                <Link
                  to="/organizer/events/create"
                  className="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium shadow-sm hover:bg-indigo-700 transition-colors"
                >
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                  </svg>
                  Create New Event
                </Link>
                {eventsLoading ? (
                  Array.from({ length: 3 }).map((_, i) => (
                    <DashboardEventCard key={`skeleton-${i}`} event={{ 
                      id: 'skeleton', 
                      title: '', 
                      bannerUrl: '', 
                      status: 'active' 
                    }} isExpanded={false} onToggle={() => {}} />
                  ))
                ) : Object.values(eventMap).map((event: EventData) => {
                  const isExpanded = expandedEventId === event.id;
                  return (
                    <DashboardEventCard
                      key={event.id}
                      event={event}
                      isExpanded={isExpanded}
                      onToggle={() => setExpandedEventId(isExpanded ? null : event.id)}
                      onViewAnalytics={() => navigate(`/organizer/events/${event.id}/analytics`)}
                      onManageInventory={() => navigate(`/organizer/events/${event.id}/inventory`)}
                      onEditEvent={() => navigate(`/organizer/events/${event.id}/edit`)}
                    />
                  );
                })}
              </div>

              {eventsError && (
                <div className="mt-4 p-4 bg-rose-50 border border-rose-200 rounded-lg">
                  <p className="text-sm text-rose-600">Failed to load events. <button onClick={refetchEvents} className="text-rose-700 underline ml-1">Retry</button></p>
                </div>
              )}

              {events.length === 0 && !eventsLoading && (
                <div className="bg-white p-6 rounded-xl border border-slate-200 mt-6">
                  <h2 className="text-center text-sm text-slate-500 mb-4">No Events</h2>
                  <p className="text-center text-slate-400">
                    You don&apos;t have any events yet.{' '}
                    <Link to="/organizer/events/create" className="font-medium text-indigo-600 hover:text-indigo-800">
                      Create your first event
                    </Link>{' '}
                    to get started.
                  </p>
                </div>
              )}

              {/* Event Actions hint */}
              <div className="bg-white p-4 rounded-xl border border-indigo-200 mt-6">
                <h3 className="text-sm font-medium text-indigo-600 mb-2">Event Actions</h3>
                <p className="text-xs text-slate-500">
                  Click any event accordion to expand and reveal:
                </p>
                <ul className="text-[10px] text-slate-500 space-y-1">
                  <li>📊 <strong>View Full Analytics</strong> — sales velocity, conversion rates, performance metrics</li>
                  <li>📦 <strong>Manage Inventory</strong> — ticket allocations and stock levels</li>
                  <li>✎ <strong>Edit Event</strong> — update title, description, venue, capacity and visibility</li>
                </ul>
                <p className="mt-2 text-[10px] text-slate-400">
                  <strong>Tip:</strong> Expand any event card to see these actions above.
                </p>
              </div>

              {/* Filter controls demonstration */}
              <div className="bg-white p-6 rounded-xl border border-slate-100 shadow-sm mt-6">
                <h2 className="text-lg font-bold text-slate-800 mb-4">Interactive Filter Store</h2>
                <p className="text-xs text-slate-500 mb-2">
                  Modify these filter preferences. Because they use Zustand&apos;s
                  <code>persist</code> middleware, they will survive page refreshes.
                  <span className="text-[10px] bg-indigo-100 px-2 py-1 rounded text-indigo-600">
                    ✨ Saved across sessions
                  </span>
                </p>
                <button
                  onClick={() => setFilters({ dateRange: 'all', status: 'all', search: '' })}
                  className="mt-2 w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-xs font-medium shadow-sm hover:bg-indigo-700 transition-colors"
                >
                  🔄 Reset Filters
                </button>
                <p className="mt-2 text-xs text-slate-500">
                  Tip: Changes persist even after closing the browser.
                </p>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
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

          </div>

          {/* Right Sidebar */}
          <div className="space-y-6">
            {/* Inventory Link */}
            <div className="bg-white p-6 rounded-xl border border-slate-100 shadow-sm">
              <h2 className="text-lg font-bold text-slate-800 mb-2">Ticket Inventory</h2>
              <p className="text-xs text-slate-500 mb-4">Manage ticket allocations and stock levels.</p>
              <Link
                to="/organizer/events/1/inventory"
                className="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-indigo-600 text-white text-xs font-bold shadow-sm hover:bg-indigo-700 transition-colors"
              >
                📦 Manage Inventory
              </Link>
            </div>

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

            {/* Sales Activity Feed */}
            {isActivityFeedVisible && (
              <div className="h-full">
                <SalesActivityFeed />
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default OrganizerDashboardPage;