import React from 'react';
import { useAuditLogs } from '../hooks/useAuditLogs';
import { AuditLogsTable, FilterPanel, ExportButton, BulkActionBar } from '../components';

const AuditLogsViewerPage = () => {
  const { loading, error, logs, filters, setFilters, bulkAction, selectedIds, setSelectedIds, refetch } = useAuditLogs();

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold text-gray-900">Audit Logs</h1>
        <ExportButton filters={filters} onExport={refetch} />
      </div>

      <FilterPanel filters={filters} onFiltersChange={setFilters} />

      {error && <div className="p-4 rounded-md bg-red-50 text-red-800">{error}</div>}

      <div className="bg-white shadow rounded-lg overflow-hidden">
        <div className="p-4 border-b">
          <BulkActionBar
            loading={loading}
            selectedCount={selectedIds?.length ?? 0}
            onAction={(action) => bulkAction(action)}
          />
        </div>

        <AuditLogsTable
          loading={loading}
          logs={logs}
          selectedIds={selectedIds}
          setSelectedIds={setSelectedIds}
        />
      </div>
    </div>
  );
};

export default AuditLogsViewerPage;

