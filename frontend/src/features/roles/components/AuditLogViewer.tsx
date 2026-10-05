import React, { useState } from 'react';
import type { AuditLogEntry } from '../types';
import './RolesComponents.css';

interface AuditLogViewerProps {
  logs: AuditLogEntry[];
  isLoading?: boolean;
  onLoadMore?: () => void;
  error?: string;
  onRetry?: () => void;
}

const AuditLogViewer: React.FC<AuditLogViewerProps> = ({ logs = [], isLoading = false, onLoadMore, error, onRetry }) => {
  const [isOpen, setIsOpen] = useState(true);

  if (isLoading) {
    return (
      <div className="audit-log-viewer" role="status" aria-label="Loading audit logs" data-testid="audit-log-viewer">
        <div className="audit-log-viewer__skeleton" />
      </div>
    );
  }

  if (error) {
    return (
      <div className="audit-log-viewer audit-log-viewer--error" role="alert" data-testid="audit-log-viewer">
        <p className="audit-log-viewer__error-message">{error}</p>
        {onRetry && (
          <button type="button" onClick={onRetry} className="audit-log-viewer__retry" data-testid="audit-log-retry">
            Retry
          </button>
        )}
      </div>
    );
  }

  return (
    <div className="audit-log-viewer" data-testid="audit-log-viewer">
      <button
        type="button"
        className="audit-log-viewer__header"
        onClick={() => setIsOpen(!isOpen)}
        aria-expanded={isOpen}
        data-testid="audit-log-header"
      >
        <span className="audit-log-viewer__title">Audit Log</span>
        <span className="audit-log-viewer__count">{logs.length}</span>
        <span className="audit-log-viewer__chevron" aria-hidden="true">
          {isOpen ? '▾' : '▸'}
        </span>
      </button>
      {isOpen && (
        <div className="audit-log-viewer__body" data-testid="audit-log-body">
          {logs.length === 0 ? (
            <p className="audit-log-viewer__empty">No audit logs yet. Administrative actions will appear here.</p>
          ) : (
            <>
              <div className="audit-log-viewer__table-wrapper">
                <table className="audit-log-viewer__table" data-testid="audit-log-table">
                  <caption className="audit-log-viewer__caption">Audit trail of administrative actions</caption>
                  <thead>
                    <tr>
                      <th scope="col">Timestamp</th>
                      <th scope="col">Admin</th>
                      <th scope="col">Target User</th>
                      <th scope="col">Action</th>
                      <th scope="col">Old Value</th>
                      <th scope="col">New Value</th>
                      <th scope="col">Reason</th>
                    </tr>
                  </thead>
                  <tbody>
                    {logs.map((log) => (
                      <tr key={log.id} data-testid={`audit-log-row-${log.id}`}>
                        <td data-testid={`audit-log-timestamp-${log.id}`}>
                          {log.createdAt ? new Date(log.createdAt).toLocaleString() : '—'}
                        </td>
                        <td data-testid={`audit-log-admin-${log.id}`}>
                          {log.adminName || log.user?.name || '—'}
                        </td>
                        <td data-testid={`audit-log-target-${log.id}`}>
                          {log.targetUser?.name || log.target_id || '—'}
                        </td>
                        <td>
                          <span className="audit-log-viewer__action">
                            {String(log.action).replace(/_/g, ' ')}
                          </span>
                        </td>
                        <td className="audit-log-viewer__muted" data-testid={`audit-log-old-${log.id}`}>
                          {log.oldValue ?? '—'}
                        </td>
                        <td className="audit-log-viewer__muted" data-testid={`audit-log-new-${log.id}`}>
                          {log.newValue ?? '—'}
                        </td>
                        <td className="audit-log-viewer__muted" data-testid={`audit-log-reason-${log.id}`}>
                          {log.reason ?? '—'}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              {onLoadMore && logs.length >= 20 && (
                <button
                  type="button"
                  className="audit-log-viewer__load-more"
                  onClick={onLoadMore}
                  data-testid="audit-log-load-more"
                >
                  Load More
                </button>
              )}
            </>
          )}
        </div>
      )}
    </div>
  );
};

export default AuditLogViewer;
