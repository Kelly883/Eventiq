import React, { useState } from 'react';
import { usePayouts } from '../hooks';
import { SummaryCards, PayoutTable, FilterBar, ExportButton } from '../components';

const OrganizerPayoutDashboardPage = () => {
  const [filters, setFilters] = useState({});
  const { payouts, summary, loading, error } = usePayouts(filters);

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">My Payouts</h1>
          <p className="text-sm text-gray-500 mt-1">Payments for your events only. For platform-wide settlement data, ask an admin.</p>
        </div>
        <ExportButton filters={filters} />
      </div>

      {error && (
        <div className="p-4 text-red-700 bg-red-100 rounded-md">
          {error}
        </div>
      )}

      <SummaryCards summary={summary} />

      <FilterBar filters={filters} onFiltersChange={setFilters} />

      <div className="bg-white shadow rounded-lg overflow-hidden">
        <PayoutTable payouts={payouts} loading={loading} />
      </div>
    </div>
  );
};

export default OrganizerPayoutDashboardPage;
