import React from 'react';
import { Link, useParams } from 'react-router-dom';

const DetailedAnalyticsPage = () => {
  const { eventId } = useParams();

  return (
    <div className="min-h-screen bg-slate-50 p-6 md:p-10">
      <div className="mx-auto max-w-3xl">
        <Link
          to={`/organizer/events/${eventId}/analytics`}
          className="text-sm font-semibold text-indigo-600 hover:text-indigo-800"
        >
          ← Back to Analytics Dashboard
        </Link>
        <div className="mt-6 bg-white rounded-xl border border-slate-200 p-12 text-center shadow-sm">
          <div className="text-4xl mb-4">📊</div>
          <h1 className="text-xl font-bold text-slate-900 mb-2">Detailed Analytics</h1>
          <p className="text-sm text-slate-500 max-w-md mx-auto">
            Transaction tables and deep-dive breakdowns for event #{eventId}
            are coming soon. Summary metrics are available on the dashboard.
          </p>
        </div>
      </div>
    </div>
  );
};

export default DetailedAnalyticsPage;
