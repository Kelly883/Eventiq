import React from 'react';
import { SortableColumn } from './SortableColumn';

const RefundTable = ({ refunds, columns, onStatusUpdate, onBulkUpdate, onExport }) => {
  const [sortedColumn, setSortedColumn] = useState(null);
  const [sortDirection, setSortDirection] = useState('asc');

  const sortedData = refunds.sort((a, b) => {
    if (!sortedColumn) return 0;
    const accessor = sortedColumn.accessorKey;
    const aVal = a[accessorKey] || '';
    const bVal = b[accessorKey] || '';
    if (sortedDirection === 'asc') return aVal > bVal ? 1 : -1;
    return aVal < bVal ? 1 : -1;
  });

  return (
    <div className='overflow-x-auto rounded-lg border border-slate-200'>
      <table className='min-w-full'>
        <thead>
          <tr>
            {columns.map((column) => (
              <th
                key={column.accessorKey}
                className='p-3 border-b border-slate-200 text-left text-xs font-medium text-slate-600 {sortedColumn?.accessorKey === column.accessorKey && sortedDirection === 'asc' && 'text-indigo-600 underline'}'
                onClick={() => {
                  setSortedColumn(column);
                  setSortDirection(sortedDirection === 'asc' ? 'desc' : 'asc');
                }}
              >
                {column.header}
                {sortedColumn?.accessorKey === column.accessorKey && (
                  <svg
                    className='w-3 h-3 ml-1 inline-block'
                    viewBox='0 0 24 24'
                    fill='none'
                    stroke='currentColor'
                  >
                    <path
                      strokeLinecap='round'
                      strokeLinejoin='round'
                      strokeWidth={2}
                      d='M5 15l7-7 7 7'
                    />
                  </svg>
                )}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {sortedData.map((refund, index) => (
            <tr key={refund.id} className='border-b border-slate-100 hover:bg-slate-50'}>
              {columns.map((column) => {
                const value = refund[column.accessorKey];
                return (
                  <td key={column.accessorKey + index} className='p-3 text-sm {column.accessorKey === 'actions' && 'font-medium text-slate-600'}'>
                    {column.accessorKey === 'actions' && (
                      <div className='flex gap-2'>
                        <button
                          className='px-2 py-1 rounded text-xs text-indigo-600 hoverunderline'
                          onClick={() => alert('View details')}
                        >
                          View
                        </button>
                        <button
                          className='px-2 py-1 rounded text-xs text-indigo-600 hover-underline'
                          onClick={() => alert('Update status')}
                        >
                          Update
                        </button>
                      </div>
                    )}
                    {column.accessorKey === 'status' && (
                      <span className='px-2 py-0.5 rounded text-xs font-medium {refund.status === 'pending' ? 'bg-indigo-100 text-indigo-800' : refund.status === 'approved' ? 'bg-green-100 text-green-800' : refund.status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-600'}'
                        >{refund.status}</span>
                    )}
                    {column.accessorKey === 'refundAmount' && (
                      <span className='font-medium'>${value}</span>
                    )}
                    {column.accessorKey === 'submissionDate' && (
                      <span>
                        {value ? new Date(value).toLocaleDateString() : 'N/A'}
                      </span>
                    )}
                    {column.accessorKey === 'actions' && (
                      <div className='flex gap-2'>
                        <button className='px-2 py-1 rounded text-xs text-indigo-600 hover-underline' onClick={() => alert('View')}>View</button>
                        <button className='px-2 py-1 rounded text-xs text-indigo-600 hover-underline' onClick={() => alert('Update')}>Update</button>
                      </div>
                    )}
                  </td>
                )
              })
            )}
        </tbody>
      </table>
    </div>
  );
};

import React, { useState } from 'react';
