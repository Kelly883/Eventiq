import React, { useState } from 'react';
import PropTypes from 'prop-types';
import { MetricCard } from '@/features/admin/components/dashboard/MetricCard';
import {
  EventCard,
  EventDetailWidget,
  SalesActivityFeed,
  DashboardHeader,
} from './index';

const METRICS = [
  { label: 'Total Revenue', value: '$48,230', trend: 'up', trendLabel: '12.5' },
  { label: 'Total Events', value: '24', trend: 'up', trendLabel: '4.2' },
  { label: 'Total Attendees', value: '1,840', trend: 'down', trendLabel: '3.1' },
  { label: 'Conversion Rate', value: '3.4%', trend: 'neutral', trendLabel: '0' },
];

const EVENTS = [
  {
    id: 1,
    title: 'Summer Festival 2026',
    description:
      'An outdoor arts and music festival with 5 stages, food trucks, and art installations.',
    date: '2026-07-15T18:00:00Z',
    location: 'Central Park, New York',
    status: 'published',
    attendees: 1250,
    capacity: 2000,
    revenue: 48230,
    thumbnail:
      'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=800&q=80',
    tiers: [
      { id: 't1', name: 'Early Bird', type: 'early-bird', price: 45, capacity: 500, sold: 480, revenue: 21600 },
      { id: 't2', name: 'Standard', type: 'standard', price: 89, capacity: 1000, sold: 620, revenue: 55180 },
      { id: 't3', name: 'VIP', type: 'vip', price: 149, capacity: 500, sold: 150, revenue: 22350 },
    ],
  },
  {
    id: 2,
    title: 'Winter Gala Dinner',
    description:
      'Premium black-tie charity dinner and live auction benefiting local arts.',
    date: '2026-12-01T19:00:00Z',
    location: 'Grand Ballroom, NYC',
    status: 'draft',
    attendees: 0,
    capacity: 300,
    revenue: 0,
    thumbnail:
      'https://images.unsplash.com/photo-1517248285471-28869991af04?w=800&q=80',
    tiers: [
      { id: 't4', name: 'Standard', type: 'standard', price: 199, capacity: 300, sold: 0, revenue: 0 },
    ],
  },
  {
    id: 3,
    title: 'Tech Summit 2026',
    description:
      'One-day conference on the future of web development and AI.',
    date: '2026-09-10T09:00:00Z',
    location: 'Convention Center, San Francisco',
    status: 'live',
    attendees: 890,
    capacity: 1000,
    revenue: 67500,
    thumbnail:
      'https://images.unsplash.com/photo-1542831371-29b0f74f9713?w=800&q=80',
    tiers: [
      { id: 't5', name: 'Early Bird', type: 'early-bird', price: 99, capacity: 300, sold: 290, revenue: 28710 },
      { id: 't6', name: 'Standard', type: 'standard', price: 149, capacity: 500, sold: 480, revenue: 71520 },
      { id: 't7', name: 'Premium', type: 'premium', price: 299, capacity: 200, sold: 120, revenue: 35880 },
    ],
  },
];

const ACTIVITIES = [
  { id: 'a1', event: 'Summer Festival 2026', tier: 'VIP', quantity: 2, price: 149.00, timestamp: new Date(Date.now() - 60000).toISOString() },
  { id: 'a2', event: 'Winter Gala Dinner', tier: 'Standard', quantity: 1, price: 199.00, timestamp: new Date(Date.now() - 180000).toISOString() },
  { id: 'a3', event: 'Tech Summit 2026', tier: 'Early Bird', quantity: 3, price: 99.00, timestamp: new Date(Date.now() - 300000).toISOString() },
  { id: 'a4', event: 'Summer Festival 2026', tier: 'Standard', quantity: 1, price: 89.00, timestamp: new Date(Date.now() - 420000).toISOString() },
];

export const DashboardDemo = () => {
  const [expandedEventId, setExpandedEventId] = useState(null);
  const [filters, setFilters] = useState({});
  const [dateRange, setDateRange] = useState('this-month');
  const [lastExport, setLastExport] = useState(null);

  const isExpanded = (id) => expandedEventId === id;

  const handleCreateEvent = () => {
    alert('Create New Event clicked — would navigate to /events/create');
  };

  const handleFilterChange = (next) => {
    setFilters((prev) => ({ ...prev, ...next }));
  };

  const handleDateRangeChange = (value) => {
    setDateRange(value);
  };

  const handleExport = (count) => {
    setLastExport(count);
    alert(`Exported ${count} activity items to CSV`);
  };

  const activeEvent = EVENTS.find((e) => e.id === expandedEventId);

  return (
    <div className="min-h-screen bg-slate-50 dark:bg-slate-950 p-4 md:p-8">
      <DashboardHeader
        title="EventIQ Dashboard"
        subtitle="Manage your events, track sales, and monitor performance"
        onCreateEvent={handleCreateEvent}
        onFilterChange={handleFilterChange}
        onDateRangeChange={handleDateRangeChange}
        filters={filters}
        dateRange={dateRange}
      />

      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        {METRICS.map((m) => (
          <MetricCard key={m.label} {...m} />
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 space-y-4">
          <div className="flex items-center justify-between">
            <h2 className="text-lg font-bold text-slate-900">Events</h2>
            <p className="text-xs text-slate-500">
              {EVENTS.length} events • {isExpanded(null) ? 'Select an event to expand' : ''}
            </p>
          </div>
          <div className="space-y-3">
            {EVENTS.map((event) => (
              <EventCard
                key={event.id}
                event={event}
                isExpanded={isExpanded(event.id)}
                onSelect={() => setExpandedEventId(event.id)}
                onEdit={(e) => alert(`Edit event: ${e.title}`)}
                onShare={(e) => alert(`Share event: ${e.title}`)}
                onDelete={(e) => {
                  if (window.confirm(`Delete "${e.title}"?`)) {
                    setExpandedEventId(null);
                  }
                }}
              />
            ))}
          </div>
        </div>

        <div className="space-y-6">
          {activeEvent && (
            <EventDetailWidget
              event={activeEvent}
              onClose={() => setExpandedEventId(null)}
              onEdit={(e) => alert(`Edit: ${e.title}`)}
              onViewTickets={(e) => alert(`View tickets: ${e.title}`)}
              onShare={(e) => alert(`Share: ${e.title}`)}
              onDelete={(e) => {
                if (window.confirm(`Delete "${e.title}"?`)) {
                  setExpandedEventId(null);
                }
              }}
            />
          )}

          <div className="h-[600px]">
            <SalesActivityFeed
              activities={ACTIVITIES}
              onFilterChange={handleFilterChange}
              onExport={handleExport}
              autoUpdate
            />
          </div>

          <div className="bg-white p-4 rounded-xl border border-slate-100 shadow-sm">
            <h3 className="text-sm font-bold text-slate-900 mb-3">Demo Controls</h3>
            <div className="space-y-2 text-xs">
              <div className="flex justify-between">
                <span className="text-slate-500">Expanded Event:</span>
                <span className="font-medium text-slate-900">
                  {activeEvent ? activeEvent.title : 'None'}
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-500">Date Range:</span>
                <span className="font-medium text-slate-900">{dateRange}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-500">Last Export:</span>
                <span className="font-medium text-slate-900">
                  {lastExport !== null ? `${lastExport} items` : 'None'}
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-500">Auto-Update:</span>
                <span className="font-medium text-green-600">Every 30s</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="mt-8 bg-indigo-50 border border-indigo-100 rounded-xl p-4">
        <h3 className="text-sm font-bold text-indigo-900 mb-2">Mobile Testing</h3>
        <p className="text-sm text-indigo-700">
          Open this page on a narrow viewport (≤640px) to verify responsive stacking. Metric cards,
          events, and the activity feed will stack vertically and remain readable.
        </p>
      </div>
    </div>
  );
};

DashboardDemo.displayName = 'DashboardDemo';

DashboardDemo.propTypes = {};

export default DashboardDemo;