import React, { useState } from 'react';
import './OrganizerProfileComponents.css';

const AuditLogViewer = ({ auditLogs = [], isLoading = false, error, onRetry }) => {
  const [isOpen, setIsOpen] = useState(true);

  if (isLoading) {
    return (
      <div className="org-audit-log-viewer" role="status" aria-label="Loading audit logs">
        <div className="org-audit-log-viewer__skeleton" />
      </div>
    );
  }

  if (error) {
    return (
      <div className="org-audit-log-viewer org-audit-log-viewer--error" role="alert">
        <p className="org-audit-log-viewer__error-message">{error}</p>
        {onRetry && (
          <button type="button" onClick={onRetry} className="org-audit-log-viewer__retry">
            Retry
          </button>
        )}
      </div>
    );
  }

  const bodyId = 'org-audit-log-body';

  return (
    <div className="org-audit-log-viewer">
      <button
        type="button"
        className="org-audit-log-viewer__header"
        onClick={() => setIsOpen(!isOpen)}
        aria-expanded={isOpen}
        aria-controls={bodyId}
      >
        <span className="org-audit-log-viewer__title">Profile Change History</span>
        <span className="org-audit-log-viewer__chevron" aria-hidden="true">
          {isOpen ? '▾' : '▸'}
        </span>
      </button>
      {isOpen && (
        <div id={bodyId} className="org-audit-log-viewer__body">
          {auditLogs.length === 0 ? (
            <p className="org-audit-log-viewer__empty">No changes yet.</p>
          ) : (
            <div className="org-audit-log-viewer__table-wrapper">
              <table className="org-audit-log-viewer__table">
                <thead>
                  <tr>
                    <th scope="col">Date</th>
                    <th scope="col">Action</th>
                    <th scope="col">Changed Fields</th>
                    <th scope="col">Old Value</th>
                    <th scope="col">New Value</th>
                  </tr>
                </thead>
                <tbody>
                  {auditLogs.map((log) => (
                    <tr key={log.id}>
                      <td>{log.createdAt ? new Date(log.createdAt).toLocaleDateString() : '—'}</td>
                      <td>
                        <span className="org-audit-log-viewer__action">
                          {String(log.action || '').replace(/_/g, ' ')}
                        </span>
                      </td>
                      <td>{log.changedFields?.join(', ') || '—'}</td>
                      <td className="org-audit-log-viewer__muted">{log.oldValue ?? '—'}</td>
                      <td className="org-audit-log-viewer__muted">{log.newValue ?? '—'}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}
    </div>
  );
};

export default AuditLogViewer;
