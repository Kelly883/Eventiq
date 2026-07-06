import React from 'react';
import { useUserManagement } from '../hooks/useUserManagement';
import { UserTable, UserDetailsPanel, BulkActionBar, UserFilters } from '../components/users';

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
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">User Management</h1>
      </div>

      <UserFilters filters={filters} onFiltersChange={setFilters} />

      {error && (
        <div className="p-4 rounded-md bg-red-50 text-red-800">{error}</div>
      )}

      <div className="bg-white shadow rounded-lg overflow-hidden">
        <div className="p-4 border-b">
          <BulkActionBar loading={loading} onAction={bulkAction} />
        </div>

        <div className="p-4 grid grid-cols-1 lg:grid-cols-3 gap-4">
          <div className="lg:col-span-2">
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
  );
};

export default AdminUserManagementPage;

