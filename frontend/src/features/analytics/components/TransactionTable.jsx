import React, { useState, useEffect, useRef } from 'react';

const columns = [
  { key: 'id', label: 'ID', width: '80px' },
  { key: 'event', label: 'Event', width: '180px' },
  { key: 'amount', label: 'Amount', width: '100px' },
  { key: 'status', label: 'Status', width: '100px' },
  { key: 'date', label: 'Date', width: '120px' },
  { key: 'paymentMethod', label: 'Payment Method', width: '140px' },
];

const getInitialRows = (count) => {
  const rows = [];
  for (let i = 0; i < count; i++) {
    rows.push({
      id: i + 1,
      event: `Event ${String.fromCharCode(65 + (i % 26))}`,
      amount: `$${(100 + i * 15).toFixed(2)}`,
      status: i % 3 === 0 ? 'Completed' : i % 3 === 1 ? 'Pending' : 'Failed',
      date: new Date(2024, 0, 1 + (i % 30)).toLocaleDateString(),
      paymentMethod: i % 4 === 0 ? 'Credit Card' : i % 4 === 1 ? 'PayPal' : i % 4 === 2 ? 'Bank Transfer' : 'Cash',
    });
  }
  return rows;
};

const TransactionTable = ({ rowCount = 10000 }) => {
  const [rows, setRows] = useState([]);
  const [sortedColumn, setSortedColumn] = useState(null);
  const [sortedDirection, setSortedDirection] = useState('asc');
  const [scrollTop, setScrollTop] = useState(0);
  const tableRef = useRef(null);

  useEffect(() => {
    setRows(getInitialRows(rowCount));
  }, [rowCount]);

  const handleScroll = (e) => {
    setScrollTop(e.target.scrollTop);
  };

  const visibleRowCount = Math.ceil(
    (window.innerHeight || 1000) / 50
  );

  const startIndex = Math.max(0, Math.floor(scrollTop / 50) - 1);
  const endIndex = Math.min(rows.length, startIndex + visibleRowCount + 2);

  const sortedRows = [...rows].sort((a, b) => {
    const aVal = a[sortedColumn];
    const bVal = b[sortedColumn];

    if (typeof aVal === 'string' && typeof bVal === 'string') {
      return sortedDirection === 'asc'
        ? aVal.localeCompare(bVal)
        : bVal.localeCompare(aVal);
    }

    return sortedDirection === 'asc' ? aVal - bVal : bVal - aVal;
  });

  const renderedRows = sortedRows.slice(startIndex, endIndex);

  const handleSort = (columnKey) => {
    if (sortedColumn === columnKey) {
      setSortedDirection(sortedDirection === 'asc' ? 'desc' : 'asc');
    } else {
      setSortedColumn(columnKey);
      setSortedDirection('asc');
    }
  };

  return (
    <div
      ref={tableRef}
      className="w-full overflow-auto border border-slate-100 rounded-lg bg-white shadow-sm"
      onScroll={handleScroll}
    >
      <table className="w-full min-w-[max-content]">
        <thead>
          <tr>
            {columns.map((col) => (
              <th
                key={col.key}
                className="px-4 py-3 text-xs font-medium text-slate-600 border-b border-slate-100"
                onClick={() => handleSort(col.key)}
                style={sortedColumn === col.key ? { cursor: 'pointer' } : {}}
              >
                {col.label}{sortedColumn === col.key && (
                  <span className="ml-1 text-[10px]">
                    {sortedDirection === 'asc' ? '▲' : '▼'}
                  </span>
                )}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {renderedRows.map((row) => (
            <tr key={row.id} className="border-b border-slate-100">
              {columns.map((col) => (
                <td
                  key={col.key}
                  className="px-4 py-2 text-sm text-slate-700"
                >
                  {row[col.key]}
                </td>
              ))}
            </tr>
          ))}
          {rows.length === 0 && (
            <tr>
              <td colSpan={columns.length} className="px-4 py-8 text-center text-slate-500">
                No transactions found
              </td>
            </tr>
          )}
        </tbody>
      </table>
    </div>
  );
};

TransactionTable.columnDefs = columns;

export default TransactionTable;