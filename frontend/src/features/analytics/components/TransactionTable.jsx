import React, { useState, useEffect, useRef } from 'react';
import { FixedList as List, VariableSizeList as VariableList } from 'react-window';

const rowHeight = 50;

const columns = [
  { key: 'id', label: 'ID', width: '80px' },
  { key: 'event', label: 'Event', width: '180px' },
  { key: 'amount', label: 'Amount', width: '100px' },
  { key: 'status', label: 'Status', width: '100px' },
  { key: 'date', label: 'Date', width: '120px' },
  { key: 'paymentMethod', label: 'Payment Method', width: '140px' },
];

const Row = ({ index, style, row }) => {
  return (
    <tr
      key={row.id}
      style={style}
      className="border-b border-slate-100"
      role="row"
      onMouseEnter={() => {}}
      onMouseLeave={() => {}}
    >
      {columns.map((col) => (
        <td
          key={col.key}
          className="px-4 py-2 text-sm text-slate-700"
          style={{ wordBreak: 'break-all' }}
          role="gridcell"
        >
          {row[col.key]}
        </td>
      ))}
    </tr>
  );
};

const TransactionTable = ({ rowCount = 10000 }) => {
  const [rows, setRows] = useState(getInitialRows(rowCount));
  const [sortedColumn, setSortedColumn] = useState(null);
  const [sortedDirection, setSortedDirection] = useState('asc');

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
      className="w-full overflow-auto border border-slate-100 rounded-lg bg-white shadow-sm"
      style={{ maxHeight: 'calc(100vh - 200px)' }}
    >
      <VariableList
        height={600}
        width='100%'
        itemCount={sortedRows.length}
        itemSize={rowHeight}
        overscanItemsCount={5}
        style={{
          border: '1px solid #e2e8f0',
          borderRadius: '4px',
          overflow: 'hidden',
        }}
      >
        {({ index, style, index: visibleIndex }) => (
          <Row
            index={index}
            style={style}
            row={sortedRows[visibleIndex]}
          />
        )}
      </VariableList>
    </div>
  );
};

TransactionTable.columnDefs = columns;

export default TransactionTable;

export const getInitialRows = (count) => {
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