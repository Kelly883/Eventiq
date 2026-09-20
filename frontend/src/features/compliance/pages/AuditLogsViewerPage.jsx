import React from 'react';
import { useAuditLogs } from '../hooks/useAuditLogs';
import { AuditLogsTable, FilterPanel, ExportButton, BulkActionBar } from '../components';
import '../../../styles/shared-pages.css';

const AuditLogsViewerPage = () => {
  const { loading, error, logs, filters, setFilters, bulkAction, selectedIds, setSelectedIds, refetch } = useAuditLogs();

  return (
    <div className="spa-page">
      <div className="spa-container">
        <div className="spa-page__header">
          <h1 className="spa-page__title">Audit Logs</h1>
          <ExportButton filters={filters} onExport={refetch} />
        </div>

        <FilterPanel filters={filters} onFiltersChange={setFilters} />

        {error && <div className="spa-alert spa-alert--error">{error}</div>}

        <div className="spa-card">
          <div style={{ padding: 16, borderBottom: '1px solid #E3E4E6' }}>
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
    </div>
  );
};

export default AuditLogsViewerPage;
