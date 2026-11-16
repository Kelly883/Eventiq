import React, { useState } from 'react';
import { FraudAlertFilters } from '../types/fraud';

interface FilterBarProps {
  onFilterChange: (filters: FraudAlertFilters) => void;
  filters: FraudAlertFilters;
}

const FilterBar: React.FC<FilterBarProps> = ({ onFilterChange, filters }) => {
  const [riskLevel, setRiskLevel] = useState<FraudAlertFilters['risk_level']>(filters.risk_level ?? undefined);
  const [dateFrom, setDateFrom] = useState<string>(filters.date_from ?? '');
  const [dateTo, setDateTo] = useState<string>(filters.date_to ?? '');
  const [paymentMethod, setPaymentMethod] = useState<string | undefined>(filters.payment_method ?? undefined);
  const [search, setSearch] = useState<string>(filters.search ?? '');

  const handleRiskChange = (value: FraudAlertFilters['risk_level']) => {
    setRiskLevel(value);
    onFilterChange({
      ...filters,
      risk_level: value === 'All' ? undefined : (value as FraudAlertFilters['risk_level']),
    });
  };

  const handleDateChange = (from: string, to: string) => {
    setDateFrom(from);
    setDateTo(to);
    onFilterChange({
      ...filters,
      date_from: from || undefined,
      date_to: to || undefined,
    });
  };

  const handlePaymentChange = (value: string | undefined) => {
    setPaymentMethod(value);
    onFilterChange({
      ...filters,
      payment_method: value || undefined,
    });
  };

  const handleSearchChange = (value: string) => {
    setSearch(value);
    onFilterChange({
      ...filters,
      search: value || undefined,
    });
  };

  return (
    <div className="bg-white rounded-xl p-4 shadow-sm border mb-6" aria-label="Fraud filters">
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {/* Risk Level Filter */}
        <div>
          <label className="block text-sm font-medium text-slate-600 mb-1">Risk Level</label>
          <select
            className="w-full rounded border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            value={riskLevel ?? 'All'}
            onChange={(e) => handleRiskChange(e.target.value as FraudAlertFilters['risk_level'])}
            aria-label="Filter by risk level"
          >
            <option value="All">All</option>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
          </select>
        </div>

        {/* Date Range Filter */}
        <div>
          <label className="block text-sm font-medium text-slate-600 mb-1">Date Range</label>
          <div className="grid grid-cols-2 gap-2">
            <input
              type="date"
              className="rounded border px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
              value={dateFrom || ''}
              onChange={(e) => handleDateChange(e.target.value, dateTo || '')}
              placeholder="Start date"
              aria-label="Start date"
            />
            <input
              type="date"
              className="rounded border px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
              value={dateTo || ''}
              onChange={(e) => handleDateChange(dateFrom || '', e.target.value)}
              placeholder="End date"
              aria-label="End date"
            />
          </div>
        </div>

        {/* Payment Method Filter */}
        <div>
          <label className="block text-sm font-medium text-slate-600 mb-1">Payment Method</label>
          <select
            className="w-full rounded border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            value={payment_method ?? undefined}
            onChange={(e) => handlePaymentChange(e.target.value as string | undefined)}
            aria-label="Filter by payment method"
          >
            <option value="">All methods</option>
            {/* Payment methods would be populated dynamically */}
          </select>
        </div>

        {/* Search Filter */}
        <div className="sm:col-span-2">
          <label className="block text-sm font-medium text-slate-600 mb-1">Search</label>
          <input
            type="text"
            className="w-full rounded border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            value={search || ''}
            onChange={(e) => handleSearchChange(e.target.value)}
            placeholder="Search alerts..."
            aria-label="Search fraud alerts"
          />
        </div>

        {/* Clear Filters Button */}
        <div className="sm:col-span-2">
          <button
            type="button"
            onClick={() => onFilterChange({})}
            className="w-full rounded border px-3 py-2 text-sm font-medium text-indigo-600 hover:text-indigo-800 transition-colors"
            aria-label="Clear all filters"
          >
            Clear all filters
          </button>
        </div>
      </div>
    </div>
  );
};

export { FilterBar };