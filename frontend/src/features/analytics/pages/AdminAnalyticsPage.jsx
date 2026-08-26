import React from 'react';
import { Link } from 'react-router-dom';

const AdminAnalyticsPage = () => {
  return (
    <div className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="mx-auto max-w-7xl">
        <div className="mb-8">
          <h1 className="text-3xl font-extrabold text-slate-900 tracking-tight">
            Admin Analytics
          </h1>
          <p className="mt-2 text-sm text-slate-500">
            Platform-wide analytics and insights across all events.
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
            <h3 className="text-sm font-medium text-slate-500">Total Events</h3>
            <p className="text-3xl font-bold text-slate-900 mt-2">—</p>
          </div>
          <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
            <h3 className="text-sm font-medium text-slate-500">Total Revenue</h3>
            <p className="text-3xl font-bold text-green-600 mt-2">—</p>
          </div>
          <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
            <h3 className="text-sm font-medium text-slate-500">Active Users</h3>
            <p className="text-3xl font-bold text-indigo-600 mt-2">—</p>
          </div>
        </div>

        <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
          <h2 className="text-lg font-bold text-slate-800 mb-4">Event Analytics</h2>
          <p className="text-sm text-slate-500">
            Select an event to view detailed analytics.{' '}
            <Link to="/events" className="text-indigo-600 hover:text-indigo-800">
              Browse events
            </Link>
          </p>
        </div>
      </div>
    </div>
  );
};

export default AdminAnalyticsPage;
