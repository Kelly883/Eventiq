import React, { useState } from 'react';
import { useSettlementData } from '../hooks';
import { SummaryCards, PayoutTable, FilterBar, ExportButton } from '../components';

const AdminSettlementDashboardPage = () => {
  const [filters, setFilters] = useState({});
  const { settlements, summary, policies, loading, error, processPayout } = useSettlementData(filters);

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold text-gray-900">Settlement Dashboard</h1>
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
        <PayoutTable 
          payouts={settlements} 
          loading={loading}
          onRowClick={(payout) => {
            if (payout.status === 'pending') {
              if (confirm('Process this payout?')) {
                processPayout(payout.id);
              }
            }
          }}
        />
      </div>
    </div>
  );
};

export default AdminSettlementDashboardPage;
