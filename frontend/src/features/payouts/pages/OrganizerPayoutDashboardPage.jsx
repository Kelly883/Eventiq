import React, { useState } from 'react';
import { usePayouts } from '../hooks';
import { SummaryCards, PayoutTable, FilterBar, ExportButton } from '../components';

const OrganizerPayoutDashboardPage = () => {
  const [filters, setFilters] = useState({});
  const { payouts, summary, loading, error } = usePayouts(filters);

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold text-gray-900">Payouts Dashboard</h1>
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
