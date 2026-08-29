import React from 'react';

const RefundTable = ({ refunds, columns, onStatusUpdate, onBulkUpdate, onExport }) => {
  return (
    <div className='overflow-x-auto rounded-lg border border-slate-200'>
      <table className='min-w-full'>
        <thead>
          <tr>
            {columns.map((column) => (
              <th key={column.accessorKey} className='p-3 border-b border-slate-200 text-left text-xs font-medium text-slate-600'>
                {column.header}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {refunds.map((refund, index) => (
            <tr key={refund.id} className='border-b border-slate-100 hover:bg-slate-50'>
              {columns.map((column) => {
                const value = refund[column.accessorKey];
                return (
                  <td key={column.accessorKey + index} className='p-3 text-sm'>
                    {column.accessorKey === 'status' && (
                      <span className={['bg-indigo-100 text-indigo-800', 'bg-green-100 text-green-800', 'bg-red-100 text-red-800', 'bg-slate-100 text-slate-600'].filter(Boolean).join(' ')}>
                        {refund.status}
                      </span>
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
              )}
            )}
        </tbody>
      </table>
    </div>
  );
}

export default RefundTable;