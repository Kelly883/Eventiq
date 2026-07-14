import React from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import {
  SalesVelocityChart,
  LazyChart
} from '../components';

const SalesAnalyticsDashboardPage = () => {
  const { eventId } = useParams();
  const navigate = useNavigate();
  const selectedEventId = eventId ? parseInt(eventId, 10) : 1;

  return (
    <div className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="mx-auto max-w-7xl">
        {/* Header */}
        <div className="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">Analytics Dashboard</h1>
            <p className="mt-2 text-sm text-slate-500">
              High-performance, pre-aggregated event sales and conversion statistics.
            </p>
          </div>
          
          <div className="flex flex-wrap items-center gap-3">
            {/* Event Selector for deep linking */}
            <div className="flex items-center gap-2">
              <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Event:</span>
              <select
                value={selectedEventId}
                onChange={(e) => navigate(`/analytics/${e.target.value}`)}
                className="bg-white border border-slate-200 text-slate-700 text-xs font-bold px-3 py-1.5 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
              >
                <option value="1">Summer Festival (ID: 1)</option>
                <option value="2">Winter Gala (ID: 2)</option>
                <option value="3">Spring Concert (ID: 3)</option>
              </select>
            </div>

            <span className="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-100 flex items-center gap-1.5">
              <span className="h-1.5 w-1.5 bg-emerald-500 rounded-full animate-pulse" />
              Pre-Aggregated Server Feed
            </span>
          </div>
        </div>

        {/* Metrics Grid */}
        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
          <div className="bg-white p-6 rounded-xl border border-slate-100 shadow-sm">
            <p className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Sales Revenue</p>
            <p className="mt-2 text-2xl font-bold text-slate-800">$14,520.00</p>
            <p className="mt-1 text-xs text-emerald-600 font-medium">↑ 12.4% vs last week</p>
          </div>
          <div className="bg-white p-6 rounded-xl border border-slate-100 shadow-sm">
            <p className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Tickets Sold</p>
            <p className="mt-2 text-2xl font-bold text-slate-800">324 / 500</p>
            <p className="mt-1 text-xs text-slate-500 font-medium">64.8% capacity filled</p>
          </div>
          <div className="bg-white p-6 rounded-xl border border-slate-100 shadow-sm">
            <p className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Avg. Conversion Rate</p>
            <p className="mt-2 text-2xl font-bold text-slate-800">18.4%</p>
            <p className="mt-1 text-xs text-emerald-600 font-medium">↑ 1.8% positive swing</p>
          </div>
          <div className="bg-white p-6 rounded-xl border border-slate-100 shadow-sm">
            <p className="text-xs font-semibold text-slate-400 uppercase tracking-wider">Page View Traffic</p>
            <p className="mt-2 text-2xl font-bold text-slate-800">1,760</p>
            <p className="mt-1 text-xs text-slate-500 font-medium">Direct & social channels</p>
          </div>
        </div>

        {/* Main Chart Area wrapped in LazyChart */}
        <div className="grid grid-cols-1 gap-6 mb-8">
          <div className="w-full">
            <LazyChart height={340}>
              <SalesVelocityChart eventId={selectedEventId} />
            </LazyChart>
          </div>
        </div>

        {/* Informational Cards about performance optimizations */}
        <div className="bg-slate-100 rounded-xl p-6 border border-slate-200/60">
          <h2 className="text-sm font-bold text-slate-700 uppercase tracking-wider mb-3">Performance Architecture Notes</h2>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 text-xs text-slate-600">
            <div className="bg-white/80 backdrop-blur p-4 rounded-lg border border-slate-200/50">
              <span className="font-bold text-slate-800 block mb-1">⚡ Server Pre-Aggregation</span>
              Data is grouped and aggregated down to hourly/daily intervals inside the database engine, returning compressed trend structures to prevent DOM layout flooding.
            </div>
            <div className="bg-white/80 backdrop-blur p-4 rounded-lg border border-slate-200/50">
              <span className="font-bold text-slate-800 block mb-1">🔇 Disabled Animations</span>
              To guarantee fluid high-density rendering under multiple charts, SVG layout calculations and layout animation triggers are set to passive.
            </div>
            <div className="bg-white/80 backdrop-blur p-4 rounded-lg border border-slate-200/50">
              <span className="font-bold text-slate-800 block mb-1">👁️ Intersection Observer</span>
              Render mounts are decoupled from page load, deferred lazily until the wrapper elements cross the visible window threshold.
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default SalesAnalyticsDashboardPage;
