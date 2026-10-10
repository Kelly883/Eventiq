import React, { useState } from 'react';

const tiers = [
  { value: 'all', label: 'All Tiers' },
  { value: 'general', label: 'General Admission' },
  { value: 'vip', label: 'VIP' },
  { value: 'premium', label: 'Premium' },
  { value: 'platinum', label: 'Platinum' },
];

const FilterPanel = ({ onFilterChange }) => {
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [selected tiers, setSelectedTiers] = useState('all');

  const handleDateChange = (e) => {
    const { value, name } = e.target;
    if (name === 'from') setDateFrom(value);
    if (name === 'to') setDateTo(value);
    onFilterChange({ dateFrom, dateTo, selectedTiers });
  };

  const handleTierChange = (e) => {
    const value = e.target.value;
    if (selected tiers === 'all') {
      setSelectedTiers(value);
    } else if (selected tiers === value) {
      setSelectedTiers('all');
    } else {
      setSelectedTiers(selected tiers + ',' + value);
    }
    onFilterChange({ dateFrom, dateTo, selectedTiers });
  };

  const clearFilters = () => {
    setDateFrom('');
    setDateTo('');
    setSelectedTiers('all');
    onFilterChange({ dateFrom: '', dateTo: '', selectedTiers: 'all' });
  };

  return (
    <div className="bg-white border border-slate-100 rounded-xl p-6 shadow-sm">
      <div className="mb-6">
        <p className="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Filter Analytics</p>
        <div className="grid grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">Date From</label>
            <input
              type="date"
              name="from"
              value={dateFrom}
              onChange={handleDateChange}
              className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">Date To</label>
            <input
              type="date"
              name="to"
              value={dateTo}
              onChange={handleDateChange}
              className="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            />
          </div>
        </div>
      </div>

      <div className="mb-6">
        <p className="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Ticket Tiers</p>
        <div className="grid grid-cols-2 gap-2">
          {tiers.map((tier) => (
            <label
              key={tier.value}
              className={`flex items-center gap-2 px-3 py-1.5 rounded border ${
                selected tiers === 'all'
                  ? 'border-slate-300'
                  : selected tiers.split(',').includes(tier.value)
                  ? 'border-emerald-500 text-emerald-600'
                  : 'border-slate-300 hover:border-slate-400'
              }`}
              onClick={() => handleTierChange(event)}
            >
              <span
                className={`w-2.5 h-2.5 rounded-full flex-shrink-0 ${
                  selected tiers === 'all'
                    ? 'bg-slate-300'
                    : selected tiers.split(',').includes(tier.value)
                    ? 'bg-emerald-500'
                    : 'bg-slate-100'
                }`}
              />
              <span className="text-sm font-medium text-slate-700">{tier.label}</span>
            </label>
          ))}
        </div>
        {selected tiers !== 'all' && (
          <button
            type="button"
            onClick={clearFilters}
            className="mt-2 w-full py-2 bg-white text-emerald-600 text-sm font-medium hover:text-emerald-800 rounded-lg border border-emerald-200 transition-colors"
          >
            Clear filters
          </button>
        )}
      </div>
    </div>
  );
};

export default FilterPanel;