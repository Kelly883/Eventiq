import React, { useMemo } from 'react';
import { FraudTransaction } from '../types/fraud';

interface FraudTransactionTableProps {
  transactions: FraudTransaction[];
  isLoading: boolean;
  onRowClick?: (transaction: FraudTransaction) => void;
  sortBy?: 'amount' | 'timestamp' | 'riskScore' | 'reason';
  sortOrder?: 'asc' | 'desc';
  onSort?: (sortBy: 'amount' | 'timestamp' | 'riskScore' | 'reason') => void;
  page?: number;
  limit?: number;
  total?: number;
  onPageChange?: (page: number) => void;
}

const getSortDirection = (sortBy: string | undefined, sortOrder: string | undefined): 'asc' | 'desc' => {
  if (!sortBy) return 'desc';
  return sortOrder || 'desc';
};

const FraudTransactionTable: React.FC<FraudTransactionTableProps> = ({
  transactions,
  isLoading,
  onRowClick,
  sortBy,
  sortOrder = 'desc',
  onSort,
  page = 1,
  limit = 10,
  total = transactions.length,
  onPageChange,
}) => {
  const sortedTransactions = useMemo(() => {
    const direction = getSortDirection(sortBy, sortOrder);
    return [...transactions].sort((a, b) => {
      let aValue: string | number | Date = '';
      let bValue: string | number | Date = '';

      if (sortBy === 'amount') {
        aValue = String(a.amount);
        bValue = String(b.amount);
      } else if (sortBy === 'timestamp') {
        aValue = new Date(a.timestamp);
        bValue = new Date(b.timestamp);
      } else if (sortBy === 'riskScore') {
        aValue = a.riskScore ?? 0;
        bValue = b.riskScore ?? 0;
      } else if (sortBy === 'reason') {
        aValue = a.reason ?? '';
        bValue = b.reason ?? '';
      }

      if (typeof aValue === 'number' && typeof bValue === 'number') {
        return direction === 'asc' ? aValue - bValue : bValue - aValue;
      }
      return String(aValue).localeCompare(String(bValue));
    });
  }, [transactions, sortBy, sortOrder]);

  const pagedTransactions = useMemo(() => {
    const start = (page - 1) * limit;
    return sortedTransactions.slice(start, start + limit);
  }, [sortedTransactions, page, limit]);

  const totalPages = useMemo(() => Math.ceil(total / limit) || 1, [total, limit]);

  if (isLoading) {
    return (
      <div className="space-y-4" aria-label="Loading transactions">
        {[Array(limit)].map((_, i) => (
          <div key={i} className="skeleton row-height h-16 rounded border-t border-slate-200"/>
        ))}
      </div>
    );
  }

  return (
    <div className="overflow-x-auto" aria-label="Fraud transactions table">
      <table className="w-full rounded border border-slate-200" aria-rowindex="1">
        <thead>
          <tr className="border-b border-slate-300 bg-slate-50">
            <th
              className="p-3 text-left text-xs font-medium text-slate-600"
              onClick={() => onSort?.('amount')}
              aria-label="Sort by amount"
            >
              Amount
              <svg
                className="ml-1 w-3 h-3"
                viewBox="0 0 24 24"
                fill="currentColor"
                aria-hidden="true"
              >
                <path d="M5 15h14M12 9l-3 3 3 3" />
              </svg>
            </th>
            <th
              className="p-3 text-left text-xs font-medium text-slate-600"
              onClick={() => onSort?.('timestamp')}
              aria-label="Sort by timestamp"
            >
              Timestamp
              <svg
                className="ml-1 w-3 h-3"
                viewBox="0 0 24 24"
                fill="currentColor"
                aria-hidden="true"
              >
                <path d="M12 15h.01M8 12h8a4 4 0 010 8H8a4 4 0 010-8z" />
              </svg>
            </th>
            <th
              className="p-3 text-left text-xs font-medium text-slate-600"
              onClick={() => onSort?.('riskScore')}
              aria-label="Sort by risk score"
            >
              Risk
              <svg
                className="ml-1 w-3 h-3"
                viewBox="0 0 24 24"
                fill="currentColor"
                aria-hidden="true"
              >
                <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </th>
            <th
              className="p-3 text-left text-xs font-medium text-slate-600"
              onClick={() => onSort?.('reason')}
              aria-label="Sort by reason"
            >
              Reason
              <svg
                className="ml-1 w-3 h-3"
                viewBox="0 0 24 24"
                fill="currentColor"
                aria-hidden="true"
              >
                <path d="M18 13h1a3 3 0 003-3V7a3 3 0 00-3-3H6a3 3 0 00-3 3v3a3 3 0 003 3h6l-2 8h1a3 3 0 003 3v5a3 3 0 003 3h1" />
              </svg>
            </th>
          </tr>
        </thead>
        <tbody>
          {pagedTransactions.map((transaction, index) => (
            <tr
              key={transaction.id}
              className="border-b last:border-b-0 hover:bg-slate-50"
              onClick={() => onRowClick?.(transaction)}
              role="row"
              aria-label={`Transaction: ${transaction.id} - ${transaction.reason || 'No reason'}`}
            >
              <td className="p-3 font-medium">{new Intl.NumberFormat('en-US').format(transaction.amount)}</td>
              <td className="p-3 text-sm text-slate-500">{new Date(transaction.timestamp).toLocaleDateString()}</td>
              <td className="p-3">
                <span className={
                  transaction.riskScore 
                    ? 'risk-badge'
                    : 'text-slate-400'
                }>
                  {transaction.riskScore ?? 'N/A'}
                </span>
              </td>
              <td className="p-3 line-clamp-2">{transaction.reason || 'N/A'}</td>
            </tr>
          ))}
          {isLoading && !pagedTransactions.length && (
            <tr>
              <td colSpan={4} className="p-6 text-center text-slate-500">
                Loading transactions…
              </td>
            </tr>
          )}
        </tbody>
      </table>
    </div>
  );
};

export { FraudTransactionTable };
