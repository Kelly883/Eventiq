import React from 'react';
import { Link } from 'react-router-dom';

const AnalyticsComparisonPage = () => {
  return (
    <div className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="mx-auto max-w-3xl">
        <Link
          to="/dashboard/organizer"
          className="text-sm font-semibold text-indigo-600 hover:text-indigo-800"
        >
          ← Back to Dashboard
        </Link>
        <div className="mt-6 bg-white rounded-xl border border-slate-200 p-12 text-center shadow-sm">
          <div className="text-4xl mb-4">⚖️</div>
          <h1 className="text-xl font-bold text-slate-900 mb-2">Compare Events</h1>
          <p className="text-sm text-slate-500 max-w-md mx-auto">
            Cross-event sales comparison is coming soon. Per-event analytics are
            available on each event&apos;s dashboard.
          </p>
        </div>
      </div>
    </div>
  );
};

export default AnalyticsComparisonPage;
