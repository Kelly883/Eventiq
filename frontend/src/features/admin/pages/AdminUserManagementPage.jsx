import React from 'react';
import { useUserManagement } from '../hooks/useUserManagement';
import { UserTable, UserDetailsPanel, BulkActionBar, UserFilters } from '../components/users';
import '../../../styles/shared-pages.css';

const AdminUserManagementPage = () => {
  const {
    loading,
    error,
    users,
    selectedUser,
    filters,
    setFilters,
    bulkAction,
    setSelectedUser,
    auditLogs,
  } = useUserManagement();

  return (
    <div className="spa-page">
      <div className="spa-container">
        <div className="spa-page__header">
          <h1 className="spa-page__title">User Management</h1>
        </div>

        <UserFilters filters={filters} onFiltersChange={setFilters} />

        {error && (
          <div className="spa-alert spa-alert--error">{error}</div>
        )}

        <div className="spa-card">
          <div style={{ padding: '16px', borderBottom: '1px solid #E3E4E6' }}>
            <BulkActionBar loading={loading} onAction={bulkAction} />
          </div>

          <div className="spa-grid spa-grid--3" style={{ padding: '16px' }}>
            <div style={{ gridColumn: 'span 2' }}>
              <UserTable
                loading={loading}
                users={users}
                onRowClick={(u) => setSelectedUser(u)}
              />
            </div>
            <div>
              <UserDetailsPanel user={selectedUser} auditLogs={auditLogs} loading={loading} />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default AdminUserManagementPage;
