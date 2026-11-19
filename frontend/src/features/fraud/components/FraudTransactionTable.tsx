import React, { useMemo, useCallback } from 'react';
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

const formatCurrency = (amount: number): string =>
  new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(amount);

const formatDate = (timestamp: string | undefined): string => {
  if (!timestamp) return 'N/A';
  const date = new Date(timestamp);
  if (Number.isNaN(date.getTime())) return 'N/A';
  return date.toLocaleDateString();
};

const getSortDirection = (sortBy: string | undefined, sortOrder: string | undefined): 'asc' | 'desc' => {
  if (!sortBy) return 'desc';
  return sortOrder || 'desc';
};

const getAriaSort = (sortBy: string | undefined, sortOrder: string | undefined, column: string): string => {
  if (sortBy !== column) return 'none';
  return sortOrder === 'asc' ? 'ascending' : 'descending';
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
        aValue = a.amount ?? 0;
        bValue = b.amount ?? 0;
      } else if (sortBy === 'timestamp') {
        aValue = new Date(a.timestamp ?? 0);
        bValue = new Date(b.timestamp ?? 0);
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
      if (aValue instanceof Date && bValue instanceof Date) {
        return direction === 'asc'
          ? aValue.getTime() - bValue.getTime()
          : bValue.getTime() - aValue.getTime();
      }
      return String(aValue).localeCompare(String(bValue));
    });
  }, [transactions, sortBy, sortOrder]);

  const pagedTransactions = useMemo(() => {
    const start = (page - 1) * limit;
    return sortedTransactions.slice(start, start + limit);
  }, [sortedTransactions, page, limit]);

  const totalPages = useMemo(() => Math.ceil(total / limit) || 1, [total, limit]);

  const handleKeyDown = useCallback(
    (transaction: FraudTransaction, event: React.KeyboardEvent) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        onRowClick?.(transaction);
      }
    },
    [onRowClick]
  );

  const handlePrevPage = useCallback(() => {
    if (onPageChange && page > 1) onPageChange(page - 1);
  }, [onPageChange, page]);

  const handleNextPage = useCallback(() => {
    if (onPageChange && page < totalPages) onPageChange(page + 1);
  }, [onPageChange, page, totalPages]);

  if (isLoading) {
    return (
      <div className="space-y-4" aria-label="Loading transactions">
        {[Array(limit)].map((_, i) => (
          <div key={i} className="skeleton row-height h-16 rounded border-t border-slate-200" />
        ))}
      </div>
    );
  }

  if (transactions.length === 0) {
    return (
      <div className="space-y-4" aria-label="No transactions found">
        <p className="p-6 text-center text-slate-500">No transactions found</p>
      </div>
    );
  }

  return (
    <div className="overflow-x-auto" aria-label="Fraud transactions table">
      <table className="w-full rounded border border-slate-200" aria-rowindex="1">
        <thead>
          <tr className="border-b border-slate-300 bg-slate-50">

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
              scope="col"
              className="p-3 text-left text-xs font-medium text-slate-600 cursor-pointer select-none"
              onClick={() => onSort?.('amount')}
              aria-label="Sort by amount"
              aria-sort={getAriaSort(sortBy, sortOrder, 'amount')}
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
              scope="col"
              className="p-3 text-left text-xs font-medium text-slate-600 cursor-pointer select-none"
              onClick={() => onSort?.('timestamp')}
              aria-label="Sort by timestamp"
              aria-sort={getAriaSort(sortBy, sortOrder, 'timestamp')}
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
              scope="col"
              className="p-3 text-left text-xs font-medium text-slate-600 cursor-pointer select-none"
              onClick={() => onSort?.('riskScore')}
              aria-label="Sort by risk score"
              aria-sort={getAriaSort(sortBy, sortOrder, 'riskScore')}
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
              scope="col"
              className="p-3 text-left text-xs font-medium text-slate-600 cursor-pointer select-none"
              onClick={() => onSort?.('reason')}
              aria-label="Sort by reason"
              aria-sort={getAriaSort(sortBy, sortOrder, 'reason')}
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
              className="border-b last:border-b-0 hover:bg-slate-50 transition-colors cursor-pointer"
              role="button"
              tabIndex={0}
              onClick={() => onRowClick?.(transaction)}
              onKeyDown={(e) => handleKeyDown(transaction, e)}
              aria-label={`Transaction: ${transaction.id} - ${transaction.reason || 'No reason'}`}
            >
              <td className="p-3 font-medium">{new Intl.NumberFormat('en-US').format(transaction.amount)}</td>
              <td className="p-3 text-sm text-slate-500">{formatDate(transaction.timestamp)}</td>
              <td className="p-3">{transaction.riskScore ?? 'N/A'}</td>
              <td className="p-3 line-clamp-2" title={transaction.reason ?? undefined}>{transaction.reason || 'N/A'}</td>
            </tr>
          ))}
        </tbody>
      </table>

      {totalPages > 1 && (
        <div className="mt-4 flex items-center justify-between space-x-2" aria-label="Pagination">
          <button
            onClick={handlePrevPage}
            disabled={page === 1 || !onPageChange}
            aria-label="Previous page"
            className="rounded border px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 disabled:pointer-events-none transition-colors"
          >
            Previous
          </button>
          <span className="text-sm text-slate-600">
            Page {page} of {totalPages}
          </span>
          <button
            onClick={handleNextPage}
            disabled={page >= totalPages || !onPageChange}
            aria-label="Next page"
            className="rounded border px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 disabled:pointer-events-none transition-colors"
          >
            Next
          </button>
        </div>
      )}
    </div>
  );
};

export { FraudTransactionTable };
