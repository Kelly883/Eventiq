import React from 'react';

const RefundFilterBar = ({ filters, setFilters }) => {
  return (
    <div className='mb-4 p-4 bg-slate-50 rounded-lg border border-slate-200'>
      <form className='grid grid-cols-2 md:grid-cols-4 gap-4'>
        <div>
          <label className='block text-sm font-medium text-slate-700 mb-1'>Status</label>
          <select
            value={filters.status}
            onChange={(e) => setFilters({ ...filters, status: e.target.value })}
            className='w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500'
          >
            <option value=''>All Statuses</option>
            <option value='pending'>Pending</option>
            <option value='approved'>Approved</option>
            <option value='rejected'>Rejected</option>
            <option value='processing'>Processing</option>
            <option value='completed'>Completed</option>
          </select>
        </div>
        <div>
          <label className='block text-sm font-medium text-slate-700 mb-1'>Event</label>
          <select
            value={filters.event}
            onChange={(e) => setFilters({ ...filters, event: e.target.value })}
            className='w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500'
          >
            <option value=''>All Events</option>
            <option value='tech-conf'>Tech Conference 2024</option>
            <option value='music-fest'>Music Festival</option>
            <option value='workshop'>Workshop Series</option>
            <option value='gala'>Gala Dinner</option>
          </select>
        </div>
        <div>
          <label className='block text-sm font-medium text-slate-700 mb-1'>Reason</label>
          <select
            value={filters.reason}
            onChange={(e) => setFilters({ ...filters, reason: e.target.value })}
            className='w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500'
          >
            <option value=''>All Reasons</option>
            <option value='event_cancelled'>Event cancelled</option>
            <option value='cannot_attend'>Cannot attend</option>
            <option value='duplicate_purchase'>Duplicate purchase</option>
            <option value='other'>Other</option>
          </select>
        </div>
        <div>
          <label className='block text-sm font-medium text-slate-700 mb-1'>Search Ticket ID</label>
          <input
            type='text'
            value={filters.searchTicket}
            onChange={(e) => setFilters({ ...filters, searchTicket: e.target.value })}
            className='w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500'
          />
        </div>
        <div>
          <label className='block text-sm font-medium text-slate-700 mb-1'>Search Email</label>
          <input
            type='email'
            value={filters.searchEmail}
            onChange={(e) => setFilters({ ...filters, searchEmail: e.target.value })}
            className='w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500'
          />
        </div>
        <div className='flex items-end'>
          <button
            type='button'
            onClick={() => setFilters({
              status: '',
              event: '',
              dateRange: '',
              amount: '',
              reason: '',
              searchTicket: '',
              searchEmail: '',
            })}
            className='px-4 py-2 rounded-lg text-sm font-medium text-indigo-600 hover:bg-indigo-50'
            >
            Clear Filters
          </button>
          <button
            type='button'
            className='px-4 py-2 rounded-lg text-sm font-medium text-indigo-600 hover:bg-indigo-50'
            >Apply Filters
          </button>
        </div>
      </form>
    </div>
  );
};

export default RefundFilterBar;
