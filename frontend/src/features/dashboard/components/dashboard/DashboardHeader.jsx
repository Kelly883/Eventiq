import React, { useState } from 'react';
import LiveIndicator from '../../../analytics/components/LiveIndicator';
import { api } from '../../../../lib/api';

const DashboardHeader = ({ user, onFilterChange, onDateRangeChange, onCreateEvent }) => {
  const firstName = user?.name?.split(' ')[0] || 'there';
  const getGreeting = () => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Good morning';
    if (hour < 18) return 'Good afternoon';
    return 'Good evening';
  };

  const [selectedFilter, setSelectedFilter] = useState('all');
  const [dateRange, setDateRange] = useState({
    from: new Date(new Date().setHours(0, 0, 0, 0)),
    to: new Date(),
  });

  const handleFilterChange = (filter) => {
    setSelectedFilter(filter);
    if (onFilterChange) onFilterChange(filter);
  };

  const handleDateRangeChange = (range) => {
    setDateRange(range);
    if (onDateRangeChange) onDateRangeChange(range);
  };

  const handleCreateEvent = () => {
    if (onCreateEvent) onCreateEvent();
    else window.location.href = '/organizer/events/new';
  };

  const events = [
    { value: 'all', label: 'All Events' },
    { value: 'concert', label: 'Concerts' },
    { value: 'summit', label: 'Summits' },
    { value: 'workshop', label: 'Workshops' },
  ];

  return (
    <div className="greeting-bar">
      <div className="greeting-content flex items-center justify-between">
        <div>
          <h2 className="greeting-title">
            {getGreeting()}, {firstName}
          </h2>
          <p className="greeting-subtitle">
            Manage your tickets, discover upcoming events, and stay organized.
          </p>
        </div>

        <div className="flex items-center gap-3">
          <LiveIndicator />

          <button
            onClick={handleCreateEvent}
            className="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs font-medium text-[#333333] bg-white border border-[#E3E4E6] hover:bg-[#F7F8FA] transition-colors"
            aria-label="Create new event"
          >
            + Create New Event
          </button>

          <div className="relative">
            <select
              value={selectedFilter}
              onChange={(e) => handleFilterChange(e.target.value)}
              className="w-24 px-2 py-1 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            >
              <option value="all">All Events</option>
              {events.map((item) => (
                <option key={item.value} value={item.value}>
                  {item.label}
                </option>
              ))}
            </select>
            <svg className="absolute right-1 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
          </div>

          <div className="relative ml-2">
            <select
              className="w-40 px-2 py-1 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            >
              <option value="">Date Range</option>
              <option value="7d">Last 7 days</option>
              <option value="30d">Last 30 days</option>
              <option value="90d">Last 90 days</option>
            </select>
            <svg className="absolute right-1 top-1/2 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
          </div>
        </div>
      </div>
    </div>
  );
};

export default DashboardHeader;